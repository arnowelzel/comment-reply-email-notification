#!/bin/sh

# The script updates the Wordpress.org SVN repository after pushing
# the latest release from Github

BASE_DIR=`pwd`
TMP_DIR=$BASE_DIR/tmp

mkdir $TMP_DIR
svn co http://plugins.svn.wordpress.org/comment-reply-email-notification/ $TMP_DIR
cd $TMP_DIR/trunk
git clone --recursive https://github.com/guhemama/worpdress-comment-reply-email-notification.git tmp
cp -r tmp/* .
rm -rf tmp
rm -rf .git*
version=`head -n 1 VERSION`
cd $TMP_DIR
svn add trunk/* --force
svn ci -m "Release $version"
svn cp trunk tags/$version
svn ci -m "Tagging version $version"
rm -rf $TMP_DIR
