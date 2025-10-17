# modifed: igorpauk 2017-18

OBJS = debug.o common.o fproxyd.o

BINS = fproxyd

CC = g++ -g3 -O2 -Wall

all: ${BINS}

fproxyd: ${OBJS}
	${CC} -o $@ ${OBJS}

debug.o: debug.c debug.h
	${CC} -c debug.c

common.o: common.cpp common.h
	${CC} -c common.cpp

fproxyd.o: fproxyd.cpp fproxyd.h
	${CC} -c fproxyd.cpp

clean:
	rm -f ${BINS} *.o *.bak *~

