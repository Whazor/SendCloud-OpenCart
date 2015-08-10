from xml.dom import minidom
import os

OSMOD_XML = os.path.join(os.path.dirname(__file__),'src/install.xml')
EXTENSION_VERSION = minidom.parse(OSMOD_XML).getElementsByTagName('version')[0].firstChild.data

#Set True if you're building development releases
DEV_VERSION = False

RELEASE_VERSION = EXTENSION_VERSION
if(DEV_VERSION):
	RELEASE_VERSION += '-dev'
