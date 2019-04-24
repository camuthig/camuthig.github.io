---
title: "GraphQL Server Built on Ktor"
date: 2019-04-24
tags: ["kotlin", "graphql"]
---

_Disclaimer: I do not use Kotlin professionally, and I am learning the language and tools by building something and sharing
my experiences here. This application is not production ready and is missing crucial pieces, like production-ready error
handling. Please review and understand any code before reusing it, and feel free to leave comments on the GitHub project_

With [authentication in place on the Ktor server](/posts/ktor-social-auth), we can move on to building out the skeleton
of the GraphQL server, incorporating the authenticated user. The full source code for this part of the process can be found
[here](https://github.com/camuthig/ktor-social-graphql/pull/2/files). Topics that will be covered will be programmatically
building the GraphQL server and schema as well as accessing the authenticated user from within the GraphQL execution
context.

The first step is to finalize how the web client will provide the server with the JWT created by our authentication module.
I chose to use a session cookie because it is simple and because the API will only be consumed by my own client, if any,
allowing me to assume I won't have any CORS issues. I [installed
the session feature](https://github.com/camuthig/ktor-social-graphql/pull/2/files#diff-083499960a6541b6c22d276b2fd32672R38)
in the auth module and configured it to [verify the JWT stored on the session](https://github.com/camuthig/ktor-social-graphql/pull/2/files#diff-083499960a6541b6c22d276b2fd32672R73).
Within the validation phase, I create a new `UserPrincipal` that wraps a `User` instance, giving any authenticated
call quick access to the current user.

(Since originally writing this code, I learned a more [idiomatic](https://github.com/camuthig/ktor-social-graphql/blob/138956f762c8865eaf67f9457af541b9483dc90d/src/auth/Module.kt#L67-L79)
way to implement the same logic, and I really like the flow.)

```kotlin
    install(Sessions) {
        cookie<Session>("ktor_social_graphql_session") {
            // Be sure to give the whole domain access to the cookie
            cookie.path = "/"
        }
    }

    install(Authentication) {
        oauth("oauth") {
            // The existing oauth code
        }

        session<Session>("session") {
            validate {
                val session = sessions.get<Session>()

                if (session != null) {
                    val token = session.accessToken
                    val jwt = JwtConfiguration.verifier.verify(token)

                    val user = userRepository.getUser(jwt.subject.toInt())

                    if (user != null) {
                        UserPrincipal(user)
                    } else {
                        null
                    }
                } else {
                    null
                }
            }
        }
    }
```

To build out the GraphQL schema, I will use the [kotlin-graphql](https://github.com/ExpediaDotCom/graphql-kotlin) package.
With this package we will define the schema as a collection of classes and functions directly in Kotlin, instead of as a
standard GraphQL schema SDL. Kotlin-graphql will take each function on the provided classes and treat them as mutations or
queries on a generated GraphQL schema. For example, a single user query would look like

```kotlin
class UserQuery(private val userRepository: UserRepository) {
    fun getUser(id: Int): User? {
        return userRepository.getUser(id)
    }
}
```

Next we will make the GraphQL functions aware of the application call context, giving it access to the authenticated user.
Kotlin-graphql has the concept of a `GraphQLContext` annotated argument that can be added to any function. This argument will not be
presented on the GraphQL schema and is passed into the execution on each request. So now we will add a custom context
class to wrap the Ktor `ApplicationCall` and pass it into the new `me` function using the `@GraphQLContext` annotation.

```kotlin
class ApplicationCallContext(val call: ApplicationCall)

class UserQuery(private val userRepository: UserRepository) {
    fun getUser(id: Int): User? {
        return userRepository.getUser(id)
    }

    fun me(@GraphQLContext context: ApplicationCallContext): User? {
        return context.call.principal<UserPrincipal>()?.user
    }
}
```

And finally there is the process of defining the GraphQL server and adding it to the Ktor application. There are couple of
pieces to this, but the majority of it is creating the server and defining how to execute the query against it:

```kotlin
// Within the installGraphQL Application function
val config = SchemaGeneratorConfig(listOf("org.camuthig"))
val queries = listOf(TopLevelObject(UserQuery(userRepository)))
val schema: GraphQLSchema = toSchema(config = config, queries = queries)
val graphQL = GraphQL.newGraphQL(schema).build()

suspend fun ApplicationCall.executeQuery() {
    val request = receive<GraphQLRequest>()
    val executionInput = ExecutionInput.newExecutionInput()
        .context(ApplicationCallContext(this))
        .query(request.query)
        .operationName(request.operationName)
        .variables(request.variables)
        .build()

    graphQL.execute(executionInput)

    respond(graphQL.execute(executionInput))
}
```

To add this GraphQL server to the application we will define an installable [module](https://github.com/camuthig/ktor-social-graphql/pull/2/files#diff-7a626779c3a6f9bacd920c43a1aa6406R23),
just like with the authentication. Along with the changes noted above, the module also configures the `GET` and `POST` routes
and sets up a static route to serve up the HTML version of the GraphQL Playground, tweaking it slightly to default to
sending cookies for authentication.

And that has the server up and running. The next step will be to design out the domain of the application and create
the necessary GraphQL queries and mutations for a client to be able to interact with it.
