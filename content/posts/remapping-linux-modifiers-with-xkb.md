---
title: "Remapping Linux Modifiers with XKB"
date: 2019-10-13
tags: ["linux"]
draft: true
summary: "Switching to Linux from MacOS can mean relearning key bindings. To make this easier I decided to remap
my modifier keys to allow me to continue using MacOS bindings in certain cirumstances. I quickly learned that it is not
always easy to make these remappings, though."
---

I find myself more frequently working on my Linux laptop, running PopOS, transitioning from a Macbook Pro.
A pain point in this transition is breaking the muscle memory regarding shortcuts that I built over
the years that make repetitive movements efficient. I decided that I like the MacOS hotkeys for the JetBrains suite of
products, which I use daily, so I set off this week to find a way to allow me to use those without breaking the rest of
the Linux ecosystem.

The MacOS shortcuts in JetBrains rely on the `cmd` key, which is considered `meta` in Linux, so I knew I at least needed
to get `meta` mapped to a key I could easily access. Because I'm already used to this key being the Windows key on my
keyboard, I decided to place it there. Unfortunately, this is also the `super` key in the Linux ecosystem, which
controls most of the navigation and global hotkeys. So along with mapping `meta` to the Windows key, I also needed to
find a way to still access the global hotkeys. I decided a good way to do this was to leverage my currently useless
Caps Lock as a `hyper` key and remap the common Linux mappings to use `hyper` instead of `super`. In testing these changes
I found there to be some global hotkeys leveraging `super` that I couldn't figure out how to remap to another key, though.
So on top of moving the commands to use `hyper` instead of `super`, I also decided to remap `super` to a different key
such that hitting the Windows key wouldn't accidentally collide with other effects.

So the final goal was:

- Caps Lock -> Hyper
- Left Windows -> Meta
- Right Alt -> Super (I don't use right alt often otherwise)

I originally tackled this problem using xmodmap, as there were a number of solutions online for just this. The solution
worked perfectly, however, I kept losing the mapping every time the computer went to sleep and would have to run
`xmodmap ~/.Xmodmap` manually to get them back.

I am running PopOS, which uses Gnome and X11 under the hood. When digging into what might be wrong, I learned that the
[Gnome system stopped loading the `.Xmodmap` some time ago](https://bugzilla.redhat.com/show_bug.cgi?id=873656). The
correct solution was to use xkb mappings instead, so I started digging into how to do that.

Long story short, I really dislike xkb and still don't really understand what is going on. I do have a passable
solution though. So let's cover what I did.

**Warning Making changes to your xkb files can cause the X server boot to crash, resulting in your keyboard not working
on the login page for PopOS as or the GUI just not loading.** I got myself into this spot once. I was able to get the system
to boot and let me login by going into a terminal directly. I did this by editing the systemd settings at the bootloader (hold the
space and esc keys while booting), then hit the `E` key and update the properties to include `systemd.unit=multi-user.target`.

1. I created a [custom mapping file](https://github.com/camuthig/env/commit/bc5b5956814e22e1a2cd3c2ab6b584acfdbc0b30).
This file is self documenting, so I won't go into what each part is doing here.
1. I moved this file into `/usr/share/X11/xkb/symbols` and named it just `mymods`.
1. I updated the `/usr/share/X11/xkb/rules/evdev` file to include the line `mymods = +mymods` directly under the line
that looks like `! option = symbol`
1. I updated the `/usr/share/X11/xkb/rules/evdev.lst` file to include the line
{{< highlight php "linenos=" >}}
mymods               Add custom modifier mappings for hyper, super, and meta
{{< / highlight >}}
directly under the line that looks like `! option`
1. I added the following XML in `/usr/share/X11/xkb/rules/evdev.xml` nested under the `<group>` inside of `<optionList>`
```xml
  <optionList>
    <group allowMultipleSelection="true">
      <option>
        <configItem>
          <name>mymods</name>
          <description>Add custom modifier mappings for hyper, super, and meta</description>
        </configItem>
      </option>
```
1. Finally, I updated my dconf files using the [dconf Editor](https://wiki.gnome.org/Projects/dconf). I added a
setting for `org.gnome.desktop.input-sources.xkb-options` as `['mymods']`.

From there, each time I started an X session, the options are applied, ensuring my key bindings still.

So what are all the parts? The custom mapping file is the bulk of the logic, it defines what keys and modifiers
I am changing and how I want them changed. The `evdev*` files are just configuration files to ensure xkb is capable
of understanding the change I made to my `xkb-options` file. I found the best way to test my mapping settings, that
doesn't require logging out of my session each time, was to run the command `setxkbmap -option "" -option mymods` to
clear out any existing options and apply my custom mapping option again.
