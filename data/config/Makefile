# $Id$
# $Author$

THISDIR=data/config
SUBDIR=

all:
	# Nothing

install:
	mkdir -p $(INSTDIR)/$(THISDIR)
	chmod a+w $(INSTDIR)/$(THISDIR)
	cp -vf .ht* *.ini $(INSTDIR)/$(THISDIR)
	for d in $(SUBDIR); do \
		make -C $$d install INSTDIR=$(INSTDIR); \
	done

clean:
	# Nothing

dist-clean:
	# Nothing
	
