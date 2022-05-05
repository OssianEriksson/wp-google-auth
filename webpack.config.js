const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const { getWebpackEntryPoints } = require('@wordpress/scripts/utils');

/**
 * Detects the list of entry points to use with webpack, combining both entry
 * points passed on the command line and entry points detected in block.json
 * files.
 *
 * @see https://webpack.js.org/concepts/entry-points/
 *
 * @return {Object<string,string>} The list of entry points.
 */
const getAllEntryPoints = () => {
	// @wordpress/scripts uses process.env.WP_ENTRY to determine entry points
	// specified as cli arguments. If it is not set, the getWebpackEntryPoints
	// instead scans for entry points in block.json files. This first call to
	// getWebpackEntryPoints thus returns block entry points
	const wpEntry = process.env.WP_ENTRY;
	delete process.env.WP_ENTRY;
	const entry = getWebpackEntryPoints();
	process.env.WP_ENTRY = wpEntry;

	// Return
	return {
		...entry,
		// As WP_ENTRY has now been reinstaded, this function call yields
		// entrypoints specified on the command line
		...getWebpackEntryPoints(),
	};
};

/* eslint-disable */
/** @type {import('webpack').Configuration} */
/* eslint-enable */
module.exports = {
	...defaultConfig,
	entry: getAllEntryPoints(),
};
