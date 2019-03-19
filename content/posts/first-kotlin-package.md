---
title: "First Kotlin Package"
date: 2019-03-27
tags: ["kotlin"]
---

I've finished and released my first Kotlin package to Bintray. It is still rough around the edges, but it is
solving problems that I am facing while building an applicatin with Ktor. Developing it has also given
me a good deal of experience with using Kotlin to solve problems.

I'm calling the package [credentials](https://bintray.com/camuthig/maven/credentials-core), and it is inspired by the `credentials` feature of Ruby on Rails.
The library allows a developer to securely store credentials in a file that is located as part of the problem, for example
`resources/credentials.conf.enc`. These are encrypted using a generated key, that should be included in the project's
`.gitignore`. The format of the file follows HOCON, and is parseable using the [Lightbend Config](https://github.com/lightbend/config) library.

Along with the core library, I have also published a [Gradle plugin](https://bintray.com/camuthig/maven/credentials-gradle)
to support mantaining the file using a Gradle project as well as to use the credentials as part of the Gradle biuld.

I was targeting two use cases as I built the project.

First I wanted to make it easier for me to access my database credentials when using Flyway with Gradle. Often, what I have
seen suggested is to use system properties to securely access values like your database username and password. I have grown
accustomed to using a .env file, though, so this felt clunky to me. Instead what I can do now is:

```kotlin
// build.gradle.kts

flyway {
    locations = arrayOf("filesystem:resources/db/migration")
    user = credentials.getString("flyway.user")
    password = credentials.getString("flyway.password")
    url = credentials.getString("flyway.url")
}
```

The `credentials` symobole is Gradle extension, supporting autocomplete in IntelliJ, and gives access to a
`getString` as well as a `getStringOrElse` function, making it easy to pull in my credentials.

The other use case I had in mind was accessing the same credentials within the Ktor application that I am building.
I'm still working out the nuances of the developer experience on this one, but as it stands now, building a datasource to
access my Postgres instance looks something like

```kotlin

object Credentials {
    private val config = ClassLoaderCredentialsStore(ClassLoader.getSystemClassLoader()).load()

    fun get(key: String, default: String = ""): String {
        if (config.hasPath(key)) {
            return config.getString(key)
        }

        return default
    }
}

fun createDataSource(): DataSource {

    val dataSource = PGSimpleDataSource()

    dataSource.setURL(Credentials.get("flyway.url"))
    dataSource.user = Credentials.get("flyway.user")
    dataSource.password = Credentials.get("flyway.password")


    return dataSource
}
```

The current `CredentialsStore` interface only defines a `load` function, which reads the files from the system and parses it
into a `Config` object. It doesn't make a ton of sense to continue reading the file from the system, however, so I plan to
iterate on the library to provide a better developer interaction.

The processing of writing these packages was really enjoyable, and the Kotlin developer experience is great. I had trouble
working with Gradle throughout the process, however. I think it was partially my fault, for trying to do things that Gradle
was not meant to do or things that were more complex than they needed to be. I also feel that Gradle is still maturing into its
Kotlin DSL, and it can be difficult to find appropriate support online when most experience over the last several years has
centered around their Groovy DSL.

All in all, I'm excited to continue exploring Kotlin. I have plans to eventually build a GraphQL server using Ktor, but I am
getting to it in a very slow manner, trying to learn from each individual piece I find I need to build. You can count on
seeing more of my attempts to use Kotlin in the near feature.