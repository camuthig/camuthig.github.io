---
title: "Pattern Matching CLI commands with Rust"
date: 2019-06-06
tags: ["rust"]
---

I'm building a CLI tool using Rust, and I ran into some difficulties figuring out the best way to
work with the [structopts](https://github.com/TeXitoi/structopt) crate using subcommands to break out my logic into separate functions. I suspect this is because I am new to Rust, but
I felt it was worth documenting the challenge and solution. 

I'll use a fake tool to demonstrate the pattern I used. Let's say the tool is called `ecko`, and
it is used to print two different kinds of messages: hellos and goodbyes. So you might `ecko hello World`
and expect to see an output like `Hello, World`. Additionally there is a global option of `-p`, for "punctuation"
to allow the user to add any punctuation to the end of any command, such as `ecko goodbye -p '!' "cruel world"` to
print `Goodbye, cruel world!`. The project implementing this example can be found on [GitHub](https://github.com/camuthig/rust-ecko).


So the first step is to define the CLI application, which the [structopt docs describe well](https://docs.rs/structopt/0.2.17/structopt/#subcommands).
Our `ecko` application will look something like this:

```rust
use quicli::prelude::*;
use structopt::StructOpt;

#[derive(Debug, StructOpt)]
#[structopt(
    name = "ecko",
    raw(setting = "structopt::clap::AppSettings::SubcommandRequiredElseHelp"))
]
struct Cli {
    #[structopt(short = "p")]
    /// The punctuation to add to the printed statement
    punctuation: Option<String>,

    #[structopt(subcommand)]
    cmd: Command,
}

#[derive(Debug, StructOpt)]
enum Command {
    #[structopt(name = "hello")]
    Hello(Hello),
    #[structopt(name = "goodbye")]
    Goodbye(Goodbye),
}

#[derive(Debug, StructOpt)]
struct Hello {
    /// The name to say hello to
    #[structopt(name = "who")]
    who: String,
}

#[derive(Debug, StructOpt)]
struct Goodbye {
    /// The name to say goodbye to
    #[structopt(name = "who")]
    who: String,
}
```

When building out CLI applications with subcommands, I like to write one function per subcommand. I
feel it keeps the logic easy to reason about and read. So the plan is to have two functions, `hello` and
`goodbye` that accept two arguments, one for the subcommand's struct and another for the overall
command's struct. This allows us to typehint everything properly and get autocompletion in our function.

```rust
fn hello(cli: &Cli, cmd: &Hello) -> Result<(), Error> {
    println!("Hello, {}{}", cmd.who, cli.punctuation.as_ref().unwrap_or(&String::new()));
    Ok(())
}

fn goodbye(cli: &Cli, cmd: &Goodbye) -> Result<(), Error> {
    println!("Goodbye, {}{}",cmd.who, cli.punctuation.as_ref().unwrap_or(&String::new()));
    Ok(())
}
```

Finally is the part I had trouble figuring out, and that is how to best match on the type of the
subcommand such that it can be known when calling the functions. I think the trouble was really just
in my being new to the language, so the Rust-compentent out there probably already know this.
However, once you call `cli.cmd` you can `match` on it and pull out the inner struct, converting
it into a variable of its own. So bringing it all together, the `main` function will be

```rust
fn main() -> CliResult {
    let cli = Cli::from_args();

    match &cli.cmd {
        Command::Hello(c) => hello(&cli, &c)?,
        Command::Goodbye(c) => goodbye(&cli, &c)?,
    }

    Ok(())
}
```
