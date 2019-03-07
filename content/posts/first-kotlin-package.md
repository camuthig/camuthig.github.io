---
title: "First Kotlin Package"
date: 2019-03-07
tags: ["kotlin"]
draft: true
---

I've got some spare time on my hands lately, and I have been trying to use that time to try out some new languages and
hopefully become comfortable enough with them to make a judgement call on whether or not I want to delve deeper into
them for my own career growth. One of the languages that I have taken notice of recently and wanted to know more about
is [Kotlin](https://kotlinlang.org/).

At a quick glance there are a number of things I like about the language. I like that it is built on the JVM. I know
that topic can be contentious, but I think it can be a great thing. The JVM is well known and stable. It can also be
run in most any web development environment, which is less of a stand out feature since containers have become so
popular. I also really like that Kotlin has been designed as a language meant to be terse and used for building
systems quickly. The language is also built by Jetbrain, which was enough for me to be interested in it even before
Google announced first class support for Kotlin within the Android environment.

So without digging too deep, I can already say that the language is stable, purpose built for uses I find important
(web development), and backed by several major players. When I recently saw that [Ktor](https://ktor.io/), an
asynchronous web framework for Kotlin reached a 1.0 release, I decided it was a good time to give the language a go.
My original plan was to build out a small GraphQL API with social authentication and a couple of basic resources.
However, I only got far enough to outline a GraphQL schema and start hooking in Google login before I decided I wanted
a better way to store the credentials. Ktor uses [Typesafe Config]() for storing application configuration, which
is similar to the `.env` files that I'm used to using, but if I want to override with an actual environment variable,
it is a bit more roundabout because of the JVM. So my thought was "why not build out a library to store an
encrypted configuration file like in Rails?" And with that, I started in on my first Kotlin package, [Credentials](https://github.com/camuthig/kotlin-credentials).

The package is obviously still very much in works, but I've had about a week of working on it to get a feel for the
language and build processes and thought I would leave some initial impressions here.

First, I'm really enjoying Kotlin. The fact that it can interop with any library compiled for the JVM means I have a
lot of giants to build off of, in this case Lightbend's configuration library. The language also feels very easy to
write. I used `Pair`s in my underlying library code, but has also used `data` classes as learning Kotlin, and I really
appreciate how easy it is to pass around typed objects. The support Jetbrain has built into the IntelliJ editor is
also great. The editor can easily help along the way, and it offers good suggestions to keep with idiomatic Kotlin.

One downside is that most of the Kotlin ecosystems are built up around the Android environment. This means that most
libraries targeting Kotlin specifically are projects meant to help Android developers. This just means that there are
tools that would be great to see for the serverside of things that either aren't there yet or are still early in their
developement. This is normally fine, because you can fall back on the Java libraries that exist, but it just might
mean that you can't use Kotlin's syntax to its fullest.

The bigger issue for me has been the build system. I have used Maven and sbt in the past for Java or Scala projects,
but with Kotlin I am trying to use Gradle, since they are also officially supporting Kotlin. Gradle, as a system,
doesn't seem to mesh well with how I want to build systems. It is hard to pin down exactly what that means, and it
might be that I am trying to get too fancy with my build files. However, I often just feel that it is a little harder
to accomplish what I would like to be able to do than I am used to in PHP. Part of this problem might stem from my
use of the Kotlin DSL instead of the Groovy DSL. The Kotlin DSL is really great, and the IntelliJ autocomplete has
come a long way on it. However, if you try to do a Google search for "how to accomplish X in Gradle", you will often
find your answers using the Groovy DSL or written using a Groovy script directly. As someone who doesn't no Groovy at
all, this can make getting the solution converted for my own needs very difficult.

On top of the difficulties of building my projects using Gradle, I found the dependency publishing process more
complex than I really wanted it to be. With PHP, building package and releasing it for others is dead simple: write
the package, have your composer.json file, go to Packagist, sync them up via GitHub. And you are done, and others can
begin using your package, even if just on your `dev-master` dependency. With Gradle and Maven, I had to first start
by learning a new versioning pattern, rather than semver and then understand that Maven and Gradle actually support
different versioning patterns. Along with that, there are a number of different repositories that might store any
given dependency you have. Both JCenter and Maven are curated repositories, so I can see how the curated pattern of
it could provide some additional security over an open store. However, it also creates one of those roadblocks that I think many developers see within the JVM ecosystem.

All in all, I'm very excited about Kotlin as a language, but I am a bit underwhelmed by the build experience I found.
I'm going to continue finetuning my library, build out a Gradle plugin for it and add it to a Ktor project, and we
will see if I come around on Gradle.
