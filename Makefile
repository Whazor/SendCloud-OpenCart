PYTHON = python

RELEASE_VERSION := $(shell $(PYTHON) -c "from helper import RELEASE_VERSION; print(RELEASE_VERSION);")

BUILD_ROOT = "dist/SendCloud-OpenCart-2-Extension-$(RELEASE_VERSION)"
BUILD_SENDCLOUD_ROOT = $(BUILD_ROOT)/upload/admin/controller/sendcloud
BUILD_DOCS_ROOT = $(BUILD_ROOT)/docs/

usage:
	echo 'Usage: make build'

build-pdf-docs:
	cd docs && make clean && make pdf
	echo 'PDF docs can be found in docs/build/pdf/'

build-extension:
	rm -rf ./dist/
	mkdir -p $(BUILD_SENDCLOUD_ROOT)

	cp ./src/install* $(BUILD_ROOT)/
	cp -r ./src/admin ./src/image $(BUILD_ROOT)/upload/
	cp -r ./src/ocmod $(BUILD_ROOT)/ocmod/
	echo 'Extension can be found in $(BUILD_ROOT)'

build-zip:
	# Create a zip for online distribution. Use Python stdlib zip to limit dependency requirements.
	$(PYTHON) -c "import shutil; shutil.make_archive('$(BUILD_ROOT)', 'zip', '$(BUILD_ROOT)')"

build: build-extension build-pdf-docs build-zip
	mkdir $(BUILD_DOCS_ROOT)
	cp -rf ./docs/build/pdf/* $(BUILD_DOCS_ROOT)
