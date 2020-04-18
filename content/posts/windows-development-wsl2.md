---
title: "Developing on Windows Using WSL 2"
date: 2020-04-18
tags: ["windows", "python", "development"]
summary: "My thoughts on trying to set up a Windows 10 development environment for Python based on WSL 2."
---

_I just ran across this draft on my machine after several months (I wrote this in January), so hopefully it is clean enough
and I believe the information is still up to date._

Below are my thoughts and experience setting up and testing the use of Window's WSL 2 system for development work. The
findings are based on running a production-ready Django application, but only doing minor modifications to test how the
development flow works.


## tl;dr

With WSL 2, Windows is nearly what I need for a development environment. The latest Slow Ring release, as of writing this,
allows me to work efficiently in the CLI in the same way I would with either MacOS or Linux. If you work entirely within
the CLI, using Vim for example, then it could be totally usable for you. Similarly, if you work mostly in VS Code, you are
also probably set with WSL 2. VS Code, as a Microsoft product, has great support for WSL and provides many of the tools developers
need.

If your development flow depends primarily on tools from the JetBrains suite of products, then WSL 2 may not be quite
game ready. For example, PyCharm can work as an editor, but many of the features do not work wonderfully well when
your files live in the WSL environment. This is because it attempts to use path variables for the WSL mount paths with
backslashes (Windows-style) instead of forward slashes when executing against the the WSL remote interpreter. If you
are really just using PyCharm for the capabilities of the editor, this may not be a deal breaker though. For example,
in my PHP work, I usually use PhpStorm, but run all commands and tests from a terminal. With Python, however, I use the
debugger in PyCharm from time to time, which I can't find a good way to make work right now.

## Setting It Up

The whole environment is based on using the new [WSL 2](https://docs.microsoft.com/en-us/windows/wsl/wsl2-about) backend
to run a real Linux kernel inside of Windows. To kick things off, if you already have the standard WSL enabled, go ahead
and add a Ubuntu distro to your system. If you don't already have WSL enabled, you can install the distro later. I suggest
the Ubuntu distro because it is the only one supported by Docker for Windows at this time.

The first step of this process is to get yourself onto the Slow Ring of the Windows Insiders Program. The
[process](https://insider.windows.com/en-us/getting-started/) is pretty simple, but it does mean losing some of your
privacy when using your Windows machine. I avoided doing this experiment for a long time because of this, but curiousity
finally got the best of me.

Once in the program, you should be able to install the latest build from the Slow Ring. This will take a while, as most
Windows updates do. After that is installed, the [instructions](https://docs.microsoft.com/en-us/windows/wsl/wsl2-install)
for setting up WSL 2 are very easy. This will also require a restart along the way.

Finally, I installed the new [Windows Terminal](https://www.microsoft.com/en-us/p/windows-terminal-preview/9n0dx20hk701)
application. I have used Cmder in the past for Windows terminal access and just never realy loved it. I find the new
Windows Terminal beta easy to use and a clean design. It is my go-to terminal on Windows now for sure.

## Some Hiccups

After I had a Ubuntu environment, I tried to set it up as I would any other Linux environment and ran into a couple of
small issues.

The first issue was that running `apt update` behind ExpressVPN failed to connect to some remote servers properly.
After updating, there have been no issues with `apt install`. Looking back, I'm not sure if this is only a WSL thing
or not, but it is something to be aware of.

The second hiccup came from my oh-my-zsh configuration as a very slow rendering when initializing or handling commands.
The behavior was similar to an [issue I found on Github](https://github.com/microsoft/WSL/issues/4256#issuecomment-568306593),
but in my case, I determined it was the size of the `PATH`
variable that created the issues. By default the Windows path is added to the Linux path in WSL 2, and when my `.zshrc`
attempted to add more information to that path, everything ground to a halt. To avoid this, I added a new `/etc/wsl.conf`
file with the following details and restarted WSL by running `wsl.exe --shutdown` and then `wsl.exe`.

```
[interop]
appendWindowsPath = false
```

This shortened my `PATH` variable greatly and made everything quick and snappy.

## Docker for WSL 2

[Docker with WSL 2 support](https://docs.docker.com/docker-for-windows/wsl-tech-preview/) was a snap to install. At the
time of writing this, the tool only fully supports Ubuntu, which is why I suggest going with that Distro, even if it
isn't your favorite.

Once installed, Docker just works within your Linux environment, including Docker Compose, which I use heavily in my
development workflow.

## Code Editor

### PyCharm

PyCharm can work as an editor, but many of the features do not work wonderfully well when your files live in the WSL
environment. This is because it attempts to use path variables for the WSL mount paths with backslashes (Windows-style)
instead of forward slashes when executing against the the WSL remote interpreter. If you are really just using PyCharm
for the capabilities of the editor, this may not be a deal breaker though. For example, in my PHP work, I usually use
PhpStorm, but run all commands and tests from a terminal.

The first thing I determined was that this required the Professional version of PyCharm. The Professional version
includes [features for using remote interpreters](https://www.jetbrains.com/help/pycharm/using-wsl-as-a-remote-interpreter.html),
including WSL.

Second, I looked into how I could make PyCharm use the git binary from my Linux environment. There is a
[tool](https://github.com/Volune/wslgit-for-jetbrains) to support this. There are some caveats about this not working
with all git commands at the time, though, so I stuck with just installing git directly to windows and having two
executables on the system.

It was also necessary to enable case sensitivity from the Windows version of Pycharm as noted in this
[issue](https://youtrack.jetbrains.com/issue/IDEA-197573#focus=streamItem-27-3866974.0-0).

Finally, I ran the commands suggested [here](https://youtrack.jetbrains.com/issue/PY-36563#focus=streamItem-27-3745830.0-0),
but based on more recent comments I don't know if it was neccessary or not. It was easy enough to do, though, and it does
not appear to have caused any issues.

### VS Code

Since PyCharm is not currently capable of providing me debugging support, I decided to look into Visual Studio (VS) Code
instead. Since this tool is built by Microsoft it has excellent WSL support. If you are comfortable working within VS Code,
then the Windows environment may be great. Personally, my muscle memory is so inherently linked to JetBrains tools that it
is hard for me to switch.

## Conclusions

The experience of setting up my development environment was good. It took less than a day to get this much figured out
and working. By the end of it I was able to set up a Django project running directly in Ubuntu, with the database running
via Docker Compose. I was able to use native Windows applications to interact with the application and make alterations
to my code quickly.

There are some things that I think this proposed Windows environment could improve upon though:

1. Better window management. I found the new [PowerToys](https://github.com/microsoft/PowerToys) library created by Microsoft
that provides some window management. This is a promising tool, but it still has a lot to work on, like allowing for keyboard
hotkeys to move a window into a particular position as well as moving windows between virtual desktops.
1. Configurable hotkeys. There are some tools that allow for you to create hotkeys in Windows, but it does require manipulating
registry keys, which isn't my favorite.
1. For Python, I need full support for PyCharm and its debugger. Maybe as I get better with the language this will be
less of an issue, but it is a deal breaker right now.

