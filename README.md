# AllTheContent Wordpress Plugin

This plugin is the first version of our importer for Wordpress.

Find more informations in the file " readme.txt "

## Git SVN

Utilisation de GIT SVN pour la syncronisation avec le serveur SVN de Wordpress

pour la documentation sur comment utiliser GIT SVN, voir ici:

https://gist.github.com/vbuzzano/2c13602fe116212ed179

## Setup workspace
### Clone svn repository with Git

    > git svn clone --no-minimize-url -s -r1130824 http://plugins.svn.wordpress.org/allthecontent/

    > cd allthecontent/

    > git svn fetch

    > git svn rebase

### Add Git Repository

Append this line to .git/config

    [remote "origin"]
    	url = https://bitbucket.org/atcfm-dev/allthecontent-wp-plugin.git
    	fetch = +refs/heads/*:refs/remotes/origin/*
    [branch "master"]
    	remote = origin
    	merge = refs/heads/master

### Pull source

    > git pull origin master

And then fix merge issues

### Push change to SVN

    > git svn dcommit

### Create a new version

 * update the "stable" version number in the readme.txt file
 * make a new tagged release in SVN with that version number
 * update the version number in the plugin's main .php file, which I think is the version number that displays
 *

### Create a tag
    > git svn tag x.x.x
