---
title: "Remapping Linux Modifiers with XKB"
date: 2019-11-02
tags: ["linux"]
draft: false
summary: "Switching to Linux from MacOS can mean relearning key bindings. To make this easier I decided to remap
my modifier keys to allow me to continue using MacOS bindings in certain cirumstances. I quickly learned that it is not
always easy to make these remappings, though."
---

# tl;dr

I remapped my keys using xkb resulting in Caps Lock being Super and the left Windows key being meta. This allowed me to
use Gnone global navigation shortcuts while still using MacOS bindings in my JetBrains IDEs.

Xkb can be really difficult to work with, so hopefully for anyone else hoping to accomplish a similar goal will be
able to use some of the information here to save some time. My xkb symbols file can be found
[here](https://github.com/camuthig/env/commit/76cb79b61a657784e139e228035d263e558be3ef).

# How I Got There

I find myself more frequently working on my Linux laptop, running PopOS, transitioning from a Macbook Pro.
A pain point in this transition is breaking the muscle memory regarding shortcuts that I built over
the years that make repetitive movements efficient. I decided that I like the MacOS hotkeys for the JetBrains suite of
products, which I use daily, so I set off over the last weeks to find a way to allow me to use those without breaking the rest of
the Linux ecosystem.

My first thought, and success, was to convert my Caps Lock key to a Hyper key using Xmodmap. I quickly learned that using Xmodmap
wasn't going to be an option based on recent changes to [how Gnome loads keymappings](https://bugzilla.redhat.com/show_bug.cgi?id=873656).
With that, my next plan was to use xkb to accomplish the same thing.

It took a while, but I was able to get a xkb mapping working to this end. It allowed me to remap Caps Lock to hyper, and
I also remapped the Windows key to be only meta, instead of also super which is the default within Gnome. Removing super
from the windows key ensured that any hotkeys that I did not or could not remap to hyper would not cause unwanted collisions.
As part of this exploration process, I began to dig into programmable keyboards as well, getting excited about the possibility
to solve my problems more thoroughly in that way.

I just received a new keyboard, programmable via QMK, and spent the day exploring possibilities with it. What I realized
along the way is that I need to find a solution that combines the software solution with the QMK solution. A solution that
would allow me to work efficiently from my new keyboard or from directly on the laptop.

The first challenge in this was that the hyper key in Linux is a virtualized key. However, with QMK, the hyper modifier
is the actual modifer (combination of all other modifier keys). So I had to rethink my xkb solution. I bang my head on
it for longer than I would like to admit before I realized that since I moved super off of the windows key, I could just
as easily use that instead of hyper on the Caps Lock for the Gnome global shortcuts. The end result being my windows
key being mapped to meta and my Caps Lock key mapped to super. My new keyboard is a Planck, so for easy access, I
configured the escape key to act as super on hold and escape on tap.

# Getting the xkb Mapping Right

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
mymods               Add custom modifier mappings for super and meta
{{< / highlight >}}
directly under the line that looks like `! option`
1. I added the following XML in `/usr/share/X11/xkb/rules/evdev.xml` nested under the `<group>` inside of `<optionList>`
```xml
  <optionList>
    <group allowMultipleSelection="true">
      <option>
        <configItem>
          <name>mymods</name>
          <description>Add custom modifier mappings for super and meta</description>
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
