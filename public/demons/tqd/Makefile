# $Id: Makefile 116 2008-02-04 16:09:33Z sen $

OTQD = vec.o debug.o common.o tqd.o

BINS = tqd

CC = gcc -g -O2

all: ${BINS}

tqd: ${OTQD}
	${CC} -o $@ ${OTQD}

clean:
	rm -f ${BINS} *.o *.bak *~

