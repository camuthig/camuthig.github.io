#! /bin/sh

echo "Deploying site..."

hugo

cd public && git add --all && git commit -m "Generating site" && git push origin master

echo "Done!"