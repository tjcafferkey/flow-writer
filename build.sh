#!/bin/bash

# Configuration
PLUGIN_NAME="flow-writer"
BUILD_DIR="dist"
ZIP_FILE="${PLUGIN_NAME}.zip"

echo "Building ${PLUGIN_NAME}..."

# Clean up previous builds
rm -rf "${BUILD_DIR}"
rm -f "${ZIP_FILE}"

# Create build directory
mkdir -p "${BUILD_DIR}/${PLUGIN_NAME}"

# Copy files
echo "Copying files..."
cp -r includes "${BUILD_DIR}/${PLUGIN_NAME}/"
cp flow-writer.php "${BUILD_DIR}/${PLUGIN_NAME}/"
cp LICENSE "${BUILD_DIR}/${PLUGIN_NAME}/"
cp README.md "${BUILD_DIR}/${PLUGIN_NAME}/"

# Create zip
echo "Creating zip file..."
cd "${BUILD_DIR}"
zip -r "../${ZIP_FILE}" "${PLUGIN_NAME}"
cd ..

# Clean up build directory
rm -rf "${BUILD_DIR}"

echo "Build complete: ${ZIP_FILE}"
