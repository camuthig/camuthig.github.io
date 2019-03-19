---
title: "Social Authentication with Ktor"
date: 2019-04-03
draft: true
---

I'm continuing to explore Kotlin as a server-side web development language and getting experience with the
available tools in that realm. My goal is to build a GraphQL server based on a simple, but real, domain. The first
step along the way in building this server is to build authentication. The end goal of this exercise is to build out
an authentication system based on OAuth2, using Google as the provider. Much of the process is obvious for anyone
familiar with Kotlin or Ktor, but as a first project, I learned a good deal along the way. The project, at the time
of writing this post, can be found [here](https://github.com/camuthig/ktor-social-graphql/tree/49670770fcd30deaa53ff28d475cd2edf4c9bb2a).
I will continue working on the project as time goes on, and have other posts planned to follow the experience.

Ktor offers the beginnings of a good [OAuth2](https://ktor.io/servers/features/authentication/oauth.html) authentication mechanism
as a feature out of the box. Where Ktor stops providing the developer tools is after receiving the OAuth access token
and deciding what to do next with it. For my use case, I don't want to continue authenticating any additional requests
using this token, so I need to take the token, verify it with the identity provider, getting the user's information,
and finally add that to my own system's database and creating a new token my system knows about.

Phew. That's still a lot. Let's look at the first part: receiving the token and verifying the identity.

This requires a few pieces. First, we need to define a route at which the login request will begin and redirect
back to, following the OAuth protocol. This is pretty straight forward and can be defined in such a way to work
for any number of identity providers using the [Location feature](https://ktor.io/servers/features/locations.html).

```kotlin
@Location("/login/{provider}/callback")
data class LoginCallback(val provider: String)
```

Next we need to define the OAuth handler that will accept requests at `/login/{provider}/callback` and implement the
authentication logic.

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

This logic accepts, the request, finds our OAuth provider configuration based on the `provider` parameter on the URL,
gets the user identity details from the OAuth provider, finds or creates the user in our local data store, and finally
returns out a JWT for further authentication. The JWT is unimportant now, and I will likely change that later. There
will be more discussion about it in another post.

The important part here is just a fews lines of code.

```kotlin
val socialIdentity = oauthConfiguration.second.getIdentity(principal.accessToken)

var user = userRepository.getUser(providerName, socialIdentity)

if (user == null) {
    user = userRepository.linkIdentity(providerName, socialIdentity)
}
```

This block of code has two encapsulations I created to make interacting with my authentication module easier. The first
is the `SocialIdentityProvider`. This encapsulation allows my code to succinctly define sending an OAuth token to an
identity provider (think Google, GitHub, Twitter, etc.), and retrieve back a subset of important data for my own
authentication module. The interface and data class are straight forward, and the concise nature of these definitions is
likely my favorite part of programming with Kotlin.

```
data class SocialIdentity(val id: String, val name: String, val nickname: String, val email: String, val avatar: String)

interface SocialIdentityProvider {
    suspend fun getIdentity(token: String): SocialIdentity
}
```

The `GoogleIdentityProvider` can be found [here](https://github.com/camuthig/ktor-social-graphql/blob/49670770fc/src/auth/social/GoogleIdentityProvider.kt).
It could be a bit more complete, but for the moment, takes care of our needs.

The second encapsulation is the idea of a `UserRepository`. I have a hit or miss relationship with the [repository pattern](https://martinfowler.com/eaaCatalog/repository.html),
but in the case of this instance, I believe it fits well, requiring a small API footprint and allowing for easier
testing later down the road. Not all of the functions in the interface are needed just yet, but I have well defined
plans for them, so they are already in there for me.

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
eager loading and navigating to relationships. I will most likely switch it out to a different implementation later,
making the repository pattern really helpful here.

I encapsulated all of this into a [module](https://github.com/camuthig/ktor-social-graphql/blob/49670770fc/src/auth/Module.kt)
and ["controller"](https://github.com/camuthig/ktor-social-graphql/blob/49670770fc/src/auth/Controller.kt) file, which is another
part of Kotlin that I feel is well suited to development with Ktor. I skimmed over some of the implementation deails,
such as configuring my OAuth provider, building the HTML for the login page, etc., so feel free to checkout the project
for more details on the implementation.