---
title: "Deploying a Hugo Blog with Travis CI"
date: 2018-12-11T21:05:29-08:00
tags: ["blogging"]
---

I have enjoyed using [Hugo](https://gohugo.io/) to build my blog, but I have not been happy with the deploy
process to GitHub Pages. GitHub user pages always deploy the master branch of the project and also does not directly
support Hugo. This means you need at least two branches to make this work, one for the source and one for the release. 
Up to this point, I was using the [pattern suggested by the Hugo team for this]
(https://gohugo.io/hosting-and-deployment/hosting-on-github/#github-user-or-organization-pages). 
This involves having one repository for the source of my blog, and then pushing the built files to a subproject that 
pointed to the repository that is deployed to GitHub Pages. I didn't love that this required having two repositories,
and the process to release was automated with a script but wasn't as clean as I would have liked. I had a chance,
recently, to use the [GitHub Pages deploy](https://docs.travis-ci.com/user/deployment/pages/) with Travis CI, and
I thought it could work well with Hugo too.

I had two goals with the transition:

1. Use a single repository for source and release
1. Be able to automate deployment such that the blog could be released each night.

To accomplish this I followed these steps.

1. Create a new, empty repository
1. Create a `source` branch, where the raw blog source will live
1. Make the `source` branch protected on GitHub, just to be safe
1. Add the `public` directory to my .gitignore
1. Add the following `.travis.yml` to my project

```yaml
install:
  # Get the release version of Hugo and unzip it
  - wget https://github.com/gohugoio/hugo/releases/download/v0.52/hugo_0.52_Linux-64bit.tar.gz
  - tar -xzvf hugo_0.52_Linux-64bit.tar.gz

script:
  # Execute the hugo build
  - ./hugo

branches:
  # I chose to build all branches aside from the master branch
  except:
    - master

deploy:
  provider: pages
  skip-cleanup: true
  # This is my GitHub personal access token that is added as an encrypted key on Travis
  github-token: $GITHUB_TOKEN
  # This tells Travis to deploy the output of the public directory to my master branch
  local-dir: public
  target-branch: master
  on:
    # This will execute the deploy process only on the source branch
    branch: source
```

With these changes, each time I push new code to the `source` branch, Travis will build the site and deploy it to
GitHub Pages. Along with that, I can configure Travis CI to build my source branch each evening, allowing me to date
my posts with a target date, and have them release automatically for me.