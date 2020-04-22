---
title: "Python Version and Dependency Management"
date: 2020-04-21
tags: ["python", "development"]
summary: "My understanding of the Python ecosystem of version and dependency management tools"
---

I have only been a professional Python developer for three months. I made this transition after several years in the PHP
ecosystem, and I am still learning some nuances of Python. One area I have found very challenging is version and
dependency management. I think it has been hard for me because it was a pretty simple concept in PHP. For versions, I
didn't often change them, but when I did, I would usually unlink the current PHP binaries and link up new ones through
some sort of script. More confusing for me, though, was dependency management. PHP's [Composer](https://getcomposer.org)
is an amazing tool and is the defacto choice in PHP-land, unless you are downloading dependencies and committing them
to your project. The Python world, however, appears to use `pip` for the most part, but then they use `distutils` and
`setup_tools` with their `setup.py` file when developing packages instead of applications. And these dependencies are
usually installed to virtual environments, instead of into a single directory within your project. It is just a lot, and
still maturing.

So there are a few important concepts in Python application development.

* Python version installation and management
* Virtual environment management
* Depdendency management

# Python Version Management

Since January 1, 2020, Python 2 is no longer supported, but I believe version management was largely important prior to
that point because the jump from Python 2 to Python 3 was a significant, long-term effort for teams. Having packages
and applications on both versions was extremely likely.

Version management allows developers to install multiple versions of the Python interpreter onto their machine and
quickly switch between them.

The best tool I have found to accomplish this is [pyenv](https://github.com/pyenv/pyenv). To install a particular
version of Python you would just

```bash
 pyenv install 3.8.1
```

Once installed, you can set your machine to use the new version globally

```bash
 pyenv global 3.8.1
```

If you instead want to just use this version when working within a specific project, you could

```bash
cd my-project
pyenv local 3.8.1
```

This will create a local `.python-version` file with just the version number. Any time you are within this `my-project`
directory or a sub-directory, you will use that version of Python.

# Virtual Environment Management

When installing dependencies in Python, they are intalled within the `site-packages` directory of the Python interpreter you
are currently using. This means that if you are working on two Python applications on the same machine, they will be
accessing the same dependencies, which may not match.

To resolve this problem, the Python community creates "virtual environments" linked to a particular version of Python, each
with their own `site-packages` directory. The developer creates a virtual envionrment and the "activates" it, using a shim
pattern to select the correct interpreter and `site-packages` directory.

I have found a few tools for managing virtual environments, each with their own strengths. So far I have been using `pyenv`
with the [virtualenv plugin](https://github.com/pyenv/pyenv-virtualenv) installed with solid results. I often forget the
commands for creating new virtual environments, but that is because I don't often use it. With the `pyenv` plugin, creating
and using a virtual environment uses either `virtualenv` or `venv`, depending on the version of Python, and gives a single
CLI interface for creating and activating environments.

```bash
pyenv virtualenv 3.8.1 blah
pyenv activate blah
pyenv deactivate blah
```

## Virtualenv

[Virtualenv](https://virtualenv.pypa.io/en/latest/) appears to be the historical go-to solution for virtual environments and
still works today. A benefit of virtualenv is that is allows the developer to use an arbitrary version of Python when creating
a new virtual environment, which is different than the officially supported `venv` tooling. The project also promotes a number of
other benefits, but at a very basic level, these don't come into play as much.

## Venv

As of Python 3.3, [venv](https://docs.python.org/3/library/venv.html) is the module officially supported by the Python language. It is easy to use
but an important difference between it and virtualenv is that it creates virtual environments based on the **current version**
of python. You cannot arbitrarily tell it a version to use. This means that before you create a new virtual environment, you
must swap to the correct version.

To use this manually instead of with the `pyenv` plugin, it would look like

```bash
pyenv local 3.8.1
python -m venv env
# activate the environment
source env/bin/activate
```

## Poetry

[Poetry](https://python-poetry.org/) will come up more in the depedency management section, but it is a new tool that I have not used professionally,
but I am excited about it because it feels much like Composer. It uses the `pyproject.toml` format defined in
[PEP 518](https://www.python.org/dev/peps/pep-0518/). With that structure, it supports having development and production
dependencies in a single file and it works for package as well as application development.

Poetry also adds basic support, similar to `venv` for virtual environment creation. The easiest way to do this is to treat
it like `venv` and select the local python version first using `pyenv`

```bash
pyenv local 3.8.1
poetry env use 3.8
# activate the virtual environment
poetry shell
```

Poetry will create a new environment for you based on the current version of Python. I don't love that the name of
the enviroment includes some unique key, but that I haven't dug into why it does that yet, either. It may be important.

# Depedency Managment

And finally there is the concept of dependency management - installing the libraries you need to build your application.

I have not done any package development in Python, so I am not touching on `distutils` and `setup_tools` here. This is
focused on installing dependencies for application development.

There is a good deal of progress being made currently to revamp dependency management including PEPs like
[508](https://www.python.org/dev/peps/pep-0508/) and [518](https://www.python.org/dev/peps/pep-0518/) that will,
in my opinion, revolutionize depedency management for Python.

## Pip

Pip appears to be the longest lasting, defacto solution for installing dependencies at this time. Pip uses a simple
requirements file format for defining dependencies and does not support any sort of lock file, though. It is also easy
to use as it is automatically installed into your virtual environments when creating them with either `virtualenv` or
`venv`.

Adding a new dependency to the environment is

```bash
 pip install django
```

A version can be specified using a version

```bash
 pip install django==3.0.1
```

Picking from a range of versions doesn't support semver, but does allow for ranges

```bash
 pip install 'django>=3.0,<4.0'
```

Pip does not support lock files, but you can install dependencies from a requirements file that might look like

```text
django>=3.0,<4.0
otherpackage==1.0.0
```

This requirements file can also be generated based on the current environment by calling `freeze` and redirecting the
output to a file

```bash
 pip freeze > requirements.txt
```

If you want some dependencies to be only for the development environments, you have to manually maintain two separate
files

```bash
pip install -r requirements.txt
pip install -r requirements-dev.txt
```

## Poetry

Poetry is still new to the Python ecosystem, having released version 1.0.0 in December 2019. It is a promising example
of where the Python ecosystem can go. Some features I really love are:

* Lock file support
* Dev and production dependencies in a single file
* Same patterns for application and package development
* Not dependent on the environment for creating the lock/freeze file
* Python version requirement setting

Honestly, Poetry _feels_ like Composer, which is probably why I am excited about it and plan to try it out in some
projects now. For those from the NodeJS world, it will feel very similar to NPM and Yarn.

Before installing dependencies, pop into the virtual environment with `poetry shell`.

To add a new dependency just

```bash
 poetry add django@^3.0
```

This will add the new dependency to your `pyproject.toml`, install it, and update your `poetry.lock`.

To add a dev dependency, just

```bash
 poetry add --dev pytest
```

Finally, if you get a clean project, you can install all of the dependencies using

```bash
 poetry install --no-root
```

