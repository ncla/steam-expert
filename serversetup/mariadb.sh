#!/usr/bin/env bash

sudo apt-get install software-properties-common
sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
sudo add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mariadb.mirror.nucleus.be/repo/10.1/ubuntu xenial main'

sudo apt update
sudo apt install mariadb-server