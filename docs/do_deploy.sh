#!/bin/bash
cd `dirname $0`
cd ..

UNPUSHED=$( git rev-list --count origin/master..master )
if [ $UNPUSHED -gt 0 ]; then
	echo "You have $UNPUSHED commit(s) on master which have not been pushed to origin"
	echo "Aborting deployment"
	exit 1
fi

# Determine and annouce the revision
REV=$(git rev-parse master);
echo "Deploying revision $REV...";
echo
git show -q "$REV" | sed 's/^/    /'
echo
echo -n "You have 5 seconds to abort"
sleep 1; echo -n  "...4"
sleep 1; echo -n  "...3"
sleep 1; echo -n  "...2"
sleep 1; echo -n  "...1"
sleep 1; echo "...GO!"
echo


echo
echo "====  PRODUCTION  ===="
echo
git push production master || exit $?

echo
echo "====  TAGGING  ===="
echo
TAG=`date +"deploy-%Y-%m-%d-%H%M"`
git tag -a "$TAG" -m "Deployment"
git push origin "$TAG"

echo
echo "====  DONE YAY  ===="
echo
