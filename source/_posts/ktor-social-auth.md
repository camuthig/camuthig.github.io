---
extends: _layouts.post
section: content
title: "Social Authentication with Ktor"
date: 2019-04-03
categories: ["kotlin"]
---

_Disclaimer: I do not use Kotlin professionally, and I am learning the language and tools by building something and sharing
my experiences here. This application is not production ready and is missing crucial pieces, like production-ready
error handling. Please be review and understand any code before reusing it, and feel free to leave comments on the GitHub project_

As I continue to explore Kotlin as a server-side web development language and get experience with the
available tools in that realm, I plan to build out a simple application, testing out a number of different tools
and patterns. The application will be a GraphQL server for a collaborative To Do list (sounds familiar, am I right?),
running on Ktor. The project can be found [here](https://github.com/camuthig/ktor-social-graphql).

This post covers my implementation of an authentication mechanism for the GraphQL server, focusing on how a user will
log into the application. The code specific to this post is based on the project
[at this commit](https://github.com/camuthig/ktor-social-graphql/tree/49670770fcd30deaa53ff28d475cd2edf4c9bb2a). Going
forward, I will try to do a better job of making pull requests within the project at each phase to make it a bit easier
to see what I am doing along the way.


Ktor offers the beginnings of a good [OAuth2](https://ktor.io/servers/features/authentication/oauth.html) authentication
mechanism out of the box. Ktor stops providing the developer tools after receiving the OAuth access token, though. It
is up to each developer to determine what should be done with this token. For my use case, I don't want to authenticate any
additional requests using this token, so I need to verify it with the identity provider (Google), which will yield the
user's information, and then I want add that to my own system's database and creating a new token my system knows about. From
there I can create my own token and return that to the consumer of my API.

Let's get into it then, starting out by verifying the OAuth access token and retrieving the user's personal information.
First, we need to define a route at which the login request will begin and redirect back to, following the OAuth protocol.
This is can be defined in such a way to work for any number of identity providers using Ktor's [Location feature](https://ktor.io/servers/features/locations.html).

```kotlin
@Location("/login/{provider}/callback")
data class LoginCallback(val provider: String)
```

Next we need to define the OAuth handler that will accept requests at `/login/{provider}/callback` and implement the
authentication logic. You can see here that I have defined my authentication routes as an extension function on the
`Routing` object. This makes it easier for me to add the whole collection of routes to my Ktor application later.

```kotlin
fun Routing.authRoutes(userRepository: UserRepository) {
    authenticate("oauth") {
        location<LoginCallback> {
            param("error") {
                // In case there is an error passed to us on the request, handle it here
                handle {
                    call.respond(HttpStatusCode.BadRequest, call.parameters.getAll("error").orEmpty())
                }
            }

            handle {
                val principal = call.authentication.principal<OAuthAccessTokenResponse.OAuth2>()

                if (principal != null) {
                    val providerName = call.application.locations.resolve<LoginCallback>(LoginCallback::class, call).provider
                    val oauthConfiguration = OAuthConfiguration.oauthProviders[providerName]
                    if (oauthConfiguration != null) {
                        // Get the identity from the OAuth provider
                        val socialIdentity = oauthConfiguration.second.getIdentity(principal.accessToken)

                        // TODO should handle errors from this call as well

                        // Find our user in our local data store
                        var user = userRepository.getUser(providerName, socialIdentity)

                        if (user == null) {
                            // If the user does not yet exist, add their identity to our data store
                            user = userRepository.linkIdentity(providerName, socialIdentity)
                        }

                        call.respond(LoginResponse(JwtConfiguration.makeToken(user)))
                    } else {
                        call.application.log.error("Missing identity provider configuration for $providerName")
                        call.respond(HttpStatusCode.NotFound)
                    }
                } else {
                    call.respond(HttpStatusCode.Unauthorized)
                }
            }
        }
    }
}
```

This logic is missing some error handling, but gets the code to where it needs to be with regards to verifying the user's
identity. The steps involved are:

1. Find the OAuth provider configuration using the `provider` parameter in the URL
1. Get the user's identity from the OAuth provider
1. Find the user in our own system, creating a new user if they do not yet exist
1. Generating our own token based on the found user

The most important part here is just a fews lines of code.

```kotlin
val socialIdentity = oauthConfiguration.second.getIdentity(principal.accessToken)

var user = userRepository.getUser(providerName, socialIdentity)

if (user == null) {
    user = userRepository.linkIdentity(providerName, socialIdentity)
}
```

This block of code has two encapsulations I created to make interacting with my authentication module easier. The first
is the `SocialIdentityProvider` (`oauthConfiguration.second.getIdentity` in this case). This encapsulation allows my
code to succinctly define sending an OAuth token to an identity provider, and retrieve back a subset of important data for my
own authentication module. The interface and data class are straight forward, and the concise nature of these definitions is
one of my favorite parts of programming with Kotlin.

```kotlin
data class SocialIdentity(val id: String, val name: String, val nickname: String, val email: String, val avatar: String)

interface SocialIdentityProvider {
    suspend fun getIdentity(token: String): SocialIdentity
}
```

The `GoogleIdentityProvider` can be found [here](https://github.com/camuthig/ktor-social-graphql/blob/49670770fc/src/auth/social/GoogleIdentityProvider.kt). This code sends a request to Google's `userinfo` route and parses the response into a
`SocialIdentity` object.

The second encapsulation is the idea of a `UserRepository`. I have a love/hate relationship with the [repository pattern](https://martinfowler.com/eaaCatalog/repository.html),
but I believe it fits well here, requiring a small API footprint and allowing for easier
testing later down the road. You may notice that I don't use all of these functions yet, but I plan to going forward,
and we will get back to that in a later post.

```kotlin
interface UserRepository {
    fun getUser(id: Int): User?

    fun getUserByEmail(email: String): User?

    fun addUser(user: User)

    fun getUser(provider: String, socialIdentity: SocialIdentity): User?

    fun linkIdentity(provider: String, socialIdentity: SocialIdentity): User
}
```

My [implementation of the repository](https://github.com/camuthig/ktor-social-graphql/blob/49670770fc/src/auth/repository/RequeryUserRepository.kt)
uses [Requery](https://github.com/requery/requery). Requery has worked well, but I have already seen some issues around
eager loading and navigating to relationships (multiple queries where a single query would do, for example). I will most
likely switch it out to a different implementation later, which is another benefit of the repository pattern.

And now I have a working authentication system built in my Ktor server, using Google as my identity provider and returning a
JWT once the user successfully logs in. The next phase of this project will be to define out the basis for the GraphQL
server and hook this authentication mechanism into it.