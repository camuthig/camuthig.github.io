---
title: "2019 Year In Review"
date: 2019-12-21
tags: ["windows", "python", "development"]
draft: true
summary: "Windows Development Summary Place"
---

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
application to see if it gives a better terminal environment than third party apps.

## Some Hiccups

After I had a Ubuntu environment, I tried to set it up as I would any other Linux environment and ran into a couple of
small issues.

The first issue was that running `apt update` behind my ExpressVPN failed to connect to some remote servers properly.
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

## PyCharm

The final piece of the puzzle for me was getting my IDE configured to work with my Linux environment. I'm moving to a
team working in Python, so that means getting PyCharm playing nicely with WSL 2.

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

## Conclusions

The experience of setting up my development environment was good. It took less than a day to get this much figured out
and working. By the end of it I was able to set up a Django project running directly in Ubuntu, with the database running
via Docker Compose. I was able to using native Windows applications to interact with the application 

### Better Window Management

### Better Hotkey Support

### PyCharm Command Execution

The command execution against files on WSL do not work out of the box. I think I have to do it as a "SSH Interpreter" instead
of a "WSL Interpreter"? Hard to say, but either way, it isn't working very well, and that means that the debugging options
in PyCharm aren't useful, which is a major benefit of the tool.

https://youtrack.jetbrains.com/issue/IDEA-171510

