#!/usr/bin/env python
import sys

#just a simulation
file = sys.argv[1][8:]

file = file[0:-2] + "out"
with open(file, 'r') as fin:
    sys.stdout.write(fin.read())

rc_file = file[0:-3] + "rc"
with open(rc_file, 'r') as fin:
    tmp = fin.read()
    exit(int(tmp))
