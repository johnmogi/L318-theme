
<!doctype html>
<html dir="rtl" lang="he-IL">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<title>בדיקת טפסי הרשמה &#8211; lilac</title>
<meta name='robots' content='noindex, nofollow' />
	<style>img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }</style>
	<link rel="alternate" type="application/rss+xml" title="lilac &laquo; פיד‏" href="https://lilac.local/feed/" />
<link rel="alternate" type="application/rss+xml" title="lilac &laquo; פיד תגובות‏" href="https://lilac.local/comments/feed/" />
<script>
window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.1.0\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/15.1.0\/svg\/","svgExt":".svg","source":{"wpemoji":"https:\/\/lilac.local\/wp-includes\/js\/wp-emoji.js?ver=6.8","twemoji":"https:\/\/lilac.local\/wp-includes\/js\/twemoji.js?ver=6.8"}};
/**
 * @output wp-includes/js/wp-emoji-loader.js
 */

/**
 * Emoji Settings as exported in PHP via _print_emoji_detection_script().
 * @typedef WPEmojiSettings
 * @type {object}
 * @property {?object} source
 * @property {?string} source.concatemoji
 * @property {?string} source.twemoji
 * @property {?string} source.wpemoji
 * @property {?boolean} DOMReady
 * @property {?Function} readyCallback
 */

/**
 * Support tests.
 * @typedef SupportTests
 * @type {object}
 * @property {?boolean} flag
 * @property {?boolean} emoji
 */

/**
 * IIFE to detect emoji support and load Twemoji if needed.
 *
 * @param {Window} window
 * @param {Document} document
 * @param {WPEmojiSettings} settings
 */
( function wpEmojiLoader( window, document, settings ) {
	if ( typeof Promise === 'undefined' ) {
		return;
	}

	var sessionStorageKey = 'wpEmojiSettingsSupports';
	var tests = [ 'flag', 'emoji' ];

	/**
	 * Checks whether the browser supports offloading to a Worker.
	 *
	 * @since 6.3.0
	 *
	 * @private
	 *
	 * @returns {boolean}
	 */
	function supportsWorkerOffloading() {
		return (
			typeof Worker !== 'undefined' &&
			typeof OffscreenCanvas !== 'undefined' &&
			typeof URL !== 'undefined' &&
			URL.createObjectURL &&
			typeof Blob !== 'undefined'
		);
	}

	/**
	 * @typedef SessionSupportTests
	 * @type {object}
	 * @property {number} timestamp
	 * @property {SupportTests} supportTests
	 */

	/**
	 * Get support tests from session.
	 *
	 * @since 6.3.0
	 *
	 * @private
	 *
	 * @returns {?SupportTests} Support tests, or null if not set or older than 1 week.
	 */
	function getSessionSupportTests() {
		try {
			/** @type {SessionSupportTests} */
			var item = JSON.parse(
				sessionStorage.getItem( sessionStorageKey )
			);
			if (
				typeof item === 'object' &&
				typeof item.timestamp === 'number' &&
				new Date().valueOf() < item.timestamp + 604800 && // Note: Number is a week in seconds.
				typeof item.supportTests === 'object'
			) {
				return item.supportTests;
			}
		} catch ( e ) {}
		return null;
	}

	/**
	 * Persist the supports in session storage.
	 *
	 * @since 6.3.0
	 *
	 * @private
	 *
	 * @param {SupportTests} supportTests Support tests.
	 */
	function setSessionSupportTests( supportTests ) {
		try {
			/** @type {SessionSupportTests} */
			var item = {
				supportTests: supportTests,
				timestamp: new Date().valueOf()
			};

			sessionStorage.setItem(
				sessionStorageKey,
				JSON.stringify( item )
			);
		} catch ( e ) {}
	}

	/**
	 * Checks if two sets of Emoji characters render the same visually.
	 *
	 * This function may be serialized to run in a Worker. Therefore, it cannot refer to variables from the containing
	 * scope. Everything must be passed by parameters.
	 *
	 * @since 4.9.0
	 *
	 * @private
	 *
	 * @param {CanvasRenderingContext2D} context 2D Context.
	 * @param {string} set1 Set of Emoji to test.
	 * @param {string} set2 Set of Emoji to test.
	 *
	 * @return {boolean} True if the two sets render the same.
	 */
	function emojiSetsRenderIdentically( context, set1, set2 ) {
		// Cleanup from previous test.
		context.clearRect( 0, 0, context.canvas.width, context.canvas.height );
		context.fillText( set1, 0, 0 );
		var rendered1 = new Uint32Array(
			context.getImageData(
				0,
				0,
				context.canvas.width,
				context.canvas.height
			).data
		);

		// Cleanup from previous test.
		context.clearRect( 0, 0, context.canvas.width, context.canvas.height );
		context.fillText( set2, 0, 0 );
		var rendered2 = new Uint32Array(
			context.getImageData(
				0,
				0,
				context.canvas.width,
				context.canvas.height
			).data
		);

		return rendered1.every( function ( rendered2Data, index ) {
			return rendered2Data === rendered2[ index ];
		} );
	}

	/**
	 * Determines if the browser properly renders Emoji that Twemoji can supplement.
	 *
	 * This function may be serialized to run in a Worker. Therefore, it cannot refer to variables from the containing
	 * scope. Everything must be passed by parameters.
	 *
	 * @since 4.2.0
	 *
	 * @private
	 *
	 * @param {CanvasRenderingContext2D} context 2D Context.
	 * @param {string} type Whether to test for support of "flag" or "emoji".
	 * @param {Function} emojiSetsRenderIdentically Reference to emojiSetsRenderIdentically function, needed due to minification.
	 *
	 * @return {boolean} True if the browser can render emoji, false if it cannot.
	 */
	function browserSupportsEmoji( context, type, emojiSetsRenderIdentically ) {
		var isIdentical;

		switch ( type ) {
			case 'flag':
				/*
				 * Test for Transgender flag compatibility. Added in Unicode 13.
				 *
				 * To test for support, we try to render it, and compare the rendering to how it would look if
				 * the browser doesn't render it correctly (white flag emoji + transgender symbol).
				 */
				isIdentical = emojiSetsRenderIdentically(
					context,
					'\uD83C\uDFF3\uFE0F\u200D\u26A7\uFE0F', // as a zero-width joiner sequence
					'\uD83C\uDFF3\uFE0F\u200B\u26A7\uFE0F' // separated by a zero-width space
				);

				if ( isIdentical ) {
					return false;
				}

				/*
				 * Test for UN flag compatibility. This is the least supported of the letter locale flags,
				 * so gives us an easy test for full support.
				 *
				 * To test for support, we try to render it, and compare the rendering to how it would look if
				 * the browser doesn't render it correctly ([U] + [N]).
				 */
				isIdentical = emojiSetsRenderIdentically(
					context,
					'\uD83C\uDDFA\uD83C\uDDF3', // as the sequence of two code points
					'\uD83C\uDDFA\u200B\uD83C\uDDF3' // as the two code points separated by a zero-width space
				);

				if ( isIdentical ) {
					return false;
				}

				/*
				 * Test for English flag compatibility. England is a country in the United Kingdom, it
				 * does not have a two letter locale code but rather a five letter sub-division code.
				 *
				 * To test for support, we try to render it, and compare the rendering to how it would look if
				 * the browser doesn't render it correctly (black flag emoji + [G] + [B] + [E] + [N] + [G]).
				 */
				isIdentical = emojiSetsRenderIdentically(
					context,
					// as the flag sequence
					'\uD83C\uDFF4\uDB40\uDC67\uDB40\uDC62\uDB40\uDC65\uDB40\uDC6E\uDB40\uDC67\uDB40\uDC7F',
					// with each code point separated by a zero-width space
					'\uD83C\uDFF4\u200B\uDB40\uDC67\u200B\uDB40\uDC62\u200B\uDB40\uDC65\u200B\uDB40\uDC6E\u200B\uDB40\uDC67\u200B\uDB40\uDC7F'
				);

				return ! isIdentical;
			case 'emoji':
				/*
				 * Rise Like a Phoenix.
				 *
				 * To test for Emoji 15.1 support, try to render a new emoji: Phoenix.
				 *
				 * A phoenix, a mythical immortal bird with flame-like feathers found in the folklore of many global
				 * cultures. Often used to symbolize renewal or rebirth.
				 *
				 * The Phoenix emoji is a ZWJ sequence combining 🐦 Bird, Zero Width Joiner and 🔥 Fire.
				 * These display as a single emoji on supported platforms.
				 *
				 * 0xD83D 0xDC26 (\uD83D\uDC26) == 🐦 Bird
				 * 0x200D                       == Zero-Width Joiner (ZWJ) that links the code points for the new emoji or
				 * 0x200B                       == Zero-Width Space (ZWS) that is rendered for clients not supporting the new emoji.
				 * 0xD83D 0xDD25 (\uD83D\uDD25) == 🔥 Fire
				 *
				 * When updating this test for future Emoji releases, ensure that individual emoji that make up the
				 * sequence come from older emoji standards.
				 */
				isIdentical = emojiSetsRenderIdentically(
					context,
					'\uD83D\uDC26\u200D\uD83D\uDD25', // as the zero-width joiner sequence
					'\uD83D\uDC26\u200B\uD83D\uDD25' // separated by a zero-width space
				);

				return ! isIdentical;
		}

		return false;
	}

	/**
	 * Checks emoji support tests.
	 *
	 * This function may be serialized to run in a Worker. Therefore, it cannot refer to variables from the containing
	 * scope. Everything must be passed by parameters.
	 *
	 * @since 6.3.0
	 *
	 * @private
	 *
	 * @param {string[]} tests Tests.
	 * @param {Function} browserSupportsEmoji Reference to browserSupportsEmoji function, needed due to minification.
	 * @param {Function} emojiSetsRenderIdentically Reference to emojiSetsRenderIdentically function, needed due to minification.
	 *
	 * @return {SupportTests} Support tests.
	 */
	function testEmojiSupports( tests, browserSupportsEmoji, emojiSetsRenderIdentically ) {
		var canvas;
		if (
			typeof WorkerGlobalScope !== 'undefined' &&
			self instanceof WorkerGlobalScope
		) {
			canvas = new OffscreenCanvas( 300, 150 ); // Dimensions are default for HTMLCanvasElement.
		} else {
			canvas = document.createElement( 'canvas' );
		}

		var context = canvas.getContext( '2d', { willReadFrequently: true } );

		/*
		 * Chrome on OS X added native emoji rendering in M41. Unfortunately,
		 * it doesn't work when the font is bolder than 500 weight. So, we
		 * check for bold rendering support to avoid invisible emoji in Chrome.
		 */
		context.textBaseline = 'top';
		context.font = '600 32px Arial';

		var supports = {};
		tests.forEach( function ( test ) {
			supports[ test ] = browserSupportsEmoji( context, test, emojiSetsRenderIdentically );
		} );
		return supports;
	}

	/**
	 * Adds a script to the head of the document.
	 *
	 * @ignore
	 *
	 * @since 4.2.0
	 *
	 * @param {string} src The url where the script is located.
	 *
	 * @return {void}
	 */
	function addScript( src ) {
		var script = document.createElement( 'script' );
		script.src = src;
		script.defer = true;
		document.head.appendChild( script );
	}

	settings.supports = {
		everything: true,
		everythingExceptFlag: true
	};

	// Create a promise for DOMContentLoaded since the worker logic may finish after the event has fired.
	var domReadyPromise = new Promise( function ( resolve ) {
		document.addEventListener( 'DOMContentLoaded', resolve, {
			once: true
		} );
	} );

	// Obtain the emoji support from the browser, asynchronously when possible.
	new Promise( function ( resolve ) {
		var supportTests = getSessionSupportTests();
		if ( supportTests ) {
			resolve( supportTests );
			return;
		}

		if ( supportsWorkerOffloading() ) {
			try {
				// Note that the functions are being passed as arguments due to minification.
				var workerScript =
					'postMessage(' +
					testEmojiSupports.toString() +
					'(' +
					[
						JSON.stringify( tests ),
						browserSupportsEmoji.toString(),
						emojiSetsRenderIdentically.toString()
					].join( ',' ) +
					'));';
				var blob = new Blob( [ workerScript ], {
					type: 'text/javascript'
				} );
				var worker = new Worker( URL.createObjectURL( blob ), { name: 'wpTestEmojiSupports' } );
				worker.onmessage = function ( event ) {
					supportTests = event.data;
					setSessionSupportTests( supportTests );
					worker.terminate();
					resolve( supportTests );
				};
				return;
			} catch ( e ) {}
		}

		supportTests = testEmojiSupports( tests, browserSupportsEmoji, emojiSetsRenderIdentically );
		setSessionSupportTests( supportTests );
		resolve( supportTests );
	} )
		// Once the browser emoji support has been obtained from the session, finalize the settings.
		.then( function ( supportTests ) {
			/*
			 * Tests the browser support for flag emojis and other emojis, and adjusts the
			 * support settings accordingly.
			 */
			for ( var test in supportTests ) {
				settings.supports[ test ] = supportTests[ test ];

				settings.supports.everything =
					settings.supports.everything && settings.supports[ test ];

				if ( 'flag' !== test ) {
					settings.supports.everythingExceptFlag =
						settings.supports.everythingExceptFlag &&
						settings.supports[ test ];
				}
			}

			settings.supports.everythingExceptFlag =
				settings.supports.everythingExceptFlag &&
				! settings.supports.flag;

			// Sets DOMReady to false and assigns a ready function to settings.
			settings.DOMReady = false;
			settings.readyCallback = function () {
				settings.DOMReady = true;
			};
		} )
		.then( function () {
			return domReadyPromise;
		} )
		.then( function () {
			// When the browser can not render everything we need to load a polyfill.
			if ( ! settings.supports.everything ) {
				settings.readyCallback();

				var src = settings.source || {};

				if ( src.concatemoji ) {
					addScript( src.concatemoji );
				} else if ( src.wpemoji && src.twemoji ) {
					addScript( src.twemoji );
					addScript( src.wpemoji );
				}
			}
		} );
} )( window, document, window._wpemojiSettings );
</script>
<style id='wp-emoji-styles-inline-css'>

	img.wp-smiley, img.emoji {
		display: inline !important;
		border: none !important;
		box-shadow: none !important;
		height: 1em !important;
		width: 1em !important;
		margin: 0 0.07em !important;
		vertical-align: -0.1em !important;
		background: none !important;
		padding: 0 !important;
	}
</style>
<style id='global-styles-inline-css'>
:root{--wp--preset--aspect-ratio--square: 1;--wp--preset--aspect-ratio--4-3: 4/3;--wp--preset--aspect-ratio--3-4: 3/4;--wp--preset--aspect-ratio--3-2: 3/2;--wp--preset--aspect-ratio--2-3: 2/3;--wp--preset--aspect-ratio--16-9: 16/9;--wp--preset--aspect-ratio--9-16: 9/16;--wp--preset--color--black: #000000;--wp--preset--color--cyan-bluish-gray: #abb8c3;--wp--preset--color--white: #ffffff;--wp--preset--color--pale-pink: #f78da7;--wp--preset--color--vivid-red: #cf2e2e;--wp--preset--color--luminous-vivid-orange: #ff6900;--wp--preset--color--luminous-vivid-amber: #fcb900;--wp--preset--color--light-green-cyan: #7bdcb5;--wp--preset--color--vivid-green-cyan: #00d084;--wp--preset--color--pale-cyan-blue: #8ed1fc;--wp--preset--color--vivid-cyan-blue: #0693e3;--wp--preset--color--vivid-purple: #9b51e0;--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%);--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg,rgb(122,220,180) 0%,rgb(0,208,130) 100%);--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg,rgba(252,185,0,1) 0%,rgba(255,105,0,1) 100%);--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg,rgba(255,105,0,1) 0%,rgb(207,46,46) 100%);--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%);--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg,rgb(74,234,220) 0%,rgb(151,120,209) 20%,rgb(207,42,186) 40%,rgb(238,44,130) 60%,rgb(251,105,98) 80%,rgb(254,248,76) 100%);--wp--preset--gradient--blush-light-purple: linear-gradient(135deg,rgb(255,206,236) 0%,rgb(152,150,240) 100%);--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg,rgb(254,205,165) 0%,rgb(254,45,45) 50%,rgb(107,0,62) 100%);--wp--preset--gradient--luminous-dusk: linear-gradient(135deg,rgb(255,203,112) 0%,rgb(199,81,192) 50%,rgb(65,88,208) 100%);--wp--preset--gradient--pale-ocean: linear-gradient(135deg,rgb(255,245,203) 0%,rgb(182,227,212) 50%,rgb(51,167,181) 100%);--wp--preset--gradient--electric-grass: linear-gradient(135deg,rgb(202,248,128) 0%,rgb(113,206,126) 100%);--wp--preset--gradient--midnight: linear-gradient(135deg,rgb(2,3,129) 0%,rgb(40,116,252) 100%);--wp--preset--font-size--small: 13px;--wp--preset--font-size--medium: 20px;--wp--preset--font-size--large: 36px;--wp--preset--font-size--x-large: 42px;--wp--preset--spacing--20: 0.44rem;--wp--preset--spacing--30: 0.67rem;--wp--preset--spacing--40: 1rem;--wp--preset--spacing--50: 1.5rem;--wp--preset--spacing--60: 2.25rem;--wp--preset--spacing--70: 3.38rem;--wp--preset--spacing--80: 5.06rem;--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);--wp--preset--shadow--outlined: 6px 6px 0px -3px rgba(255, 255, 255, 1), 6px 6px rgba(0, 0, 0, 1);--wp--preset--shadow--crisp: 6px 6px 0px rgba(0, 0, 0, 1);}:root { --wp--style--global--content-size: 800px;--wp--style--global--wide-size: 1200px; }:where(body) { margin: 0; }.wp-site-blocks > .alignleft { float: left; margin-right: 2em; }.wp-site-blocks > .alignright { float: right; margin-left: 2em; }.wp-site-blocks > .aligncenter { justify-content: center; margin-left: auto; margin-right: auto; }:where(.wp-site-blocks) > * { margin-block-start: 24px; margin-block-end: 0; }:where(.wp-site-blocks) > :first-child { margin-block-start: 0; }:where(.wp-site-blocks) > :last-child { margin-block-end: 0; }:root { --wp--style--block-gap: 24px; }:root :where(.is-layout-flow) > :first-child{margin-block-start: 0;}:root :where(.is-layout-flow) > :last-child{margin-block-end: 0;}:root :where(.is-layout-flow) > *{margin-block-start: 24px;margin-block-end: 0;}:root :where(.is-layout-constrained) > :first-child{margin-block-start: 0;}:root :where(.is-layout-constrained) > :last-child{margin-block-end: 0;}:root :where(.is-layout-constrained) > *{margin-block-start: 24px;margin-block-end: 0;}:root :where(.is-layout-flex){gap: 24px;}:root :where(.is-layout-grid){gap: 24px;}.is-layout-flow > .alignleft{float: left;margin-inline-start: 0;margin-inline-end: 2em;}.is-layout-flow > .alignright{float: right;margin-inline-start: 2em;margin-inline-end: 0;}.is-layout-flow > .aligncenter{margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > .alignleft{float: left;margin-inline-start: 0;margin-inline-end: 2em;}.is-layout-constrained > .alignright{float: right;margin-inline-start: 2em;margin-inline-end: 0;}.is-layout-constrained > .aligncenter{margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)){max-width: var(--wp--style--global--content-size);margin-left: auto !important;margin-right: auto !important;}.is-layout-constrained > .alignwide{max-width: var(--wp--style--global--wide-size);}body .is-layout-flex{display: flex;}.is-layout-flex{flex-wrap: wrap;align-items: center;}.is-layout-flex > :is(*, div){margin: 0;}body .is-layout-grid{display: grid;}.is-layout-grid > :is(*, div){margin: 0;}body{padding-top: 0px;padding-right: 0px;padding-bottom: 0px;padding-left: 0px;}a:where(:not(.wp-element-button)){text-decoration: underline;}:root :where(.wp-element-button, .wp-block-button__link){background-color: #32373c;border-width: 0;color: #fff;font-family: inherit;font-size: inherit;line-height: inherit;padding: calc(0.667em + 2px) calc(1.333em + 2px);text-decoration: none;}.has-black-color{color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-color{color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-color{color: var(--wp--preset--color--white) !important;}.has-pale-pink-color{color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-color{color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-color{color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-color{color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-color{color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-color{color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-color{color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-color{color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-color{color: var(--wp--preset--color--vivid-purple) !important;}.has-black-background-color{background-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-background-color{background-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-background-color{background-color: var(--wp--preset--color--white) !important;}.has-pale-pink-background-color{background-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-background-color{background-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-background-color{background-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-background-color{background-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-background-color{background-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-background-color{background-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-background-color{background-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-background-color{background-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-background-color{background-color: var(--wp--preset--color--vivid-purple) !important;}.has-black-border-color{border-color: var(--wp--preset--color--black) !important;}.has-cyan-bluish-gray-border-color{border-color: var(--wp--preset--color--cyan-bluish-gray) !important;}.has-white-border-color{border-color: var(--wp--preset--color--white) !important;}.has-pale-pink-border-color{border-color: var(--wp--preset--color--pale-pink) !important;}.has-vivid-red-border-color{border-color: var(--wp--preset--color--vivid-red) !important;}.has-luminous-vivid-orange-border-color{border-color: var(--wp--preset--color--luminous-vivid-orange) !important;}.has-luminous-vivid-amber-border-color{border-color: var(--wp--preset--color--luminous-vivid-amber) !important;}.has-light-green-cyan-border-color{border-color: var(--wp--preset--color--light-green-cyan) !important;}.has-vivid-green-cyan-border-color{border-color: var(--wp--preset--color--vivid-green-cyan) !important;}.has-pale-cyan-blue-border-color{border-color: var(--wp--preset--color--pale-cyan-blue) !important;}.has-vivid-cyan-blue-border-color{border-color: var(--wp--preset--color--vivid-cyan-blue) !important;}.has-vivid-purple-border-color{border-color: var(--wp--preset--color--vivid-purple) !important;}.has-vivid-cyan-blue-to-vivid-purple-gradient-background{background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;}.has-light-green-cyan-to-vivid-green-cyan-gradient-background{background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;}.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;}.has-luminous-vivid-orange-to-vivid-red-gradient-background{background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;}.has-very-light-gray-to-cyan-bluish-gray-gradient-background{background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;}.has-cool-to-warm-spectrum-gradient-background{background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;}.has-blush-light-purple-gradient-background{background: var(--wp--preset--gradient--blush-light-purple) !important;}.has-blush-bordeaux-gradient-background{background: var(--wp--preset--gradient--blush-bordeaux) !important;}.has-luminous-dusk-gradient-background{background: var(--wp--preset--gradient--luminous-dusk) !important;}.has-pale-ocean-gradient-background{background: var(--wp--preset--gradient--pale-ocean) !important;}.has-electric-grass-gradient-background{background: var(--wp--preset--gradient--electric-grass) !important;}.has-midnight-gradient-background{background: var(--wp--preset--gradient--midnight) !important;}.has-small-font-size{font-size: var(--wp--preset--font-size--small) !important;}.has-medium-font-size{font-size: var(--wp--preset--font-size--medium) !important;}.has-large-font-size{font-size: var(--wp--preset--font-size--large) !important;}.has-x-large-font-size{font-size: var(--wp--preset--font-size--x-large) !important;}
:root :where(.wp-block-pullquote){font-size: 1.5em;line-height: 1.6;}
</style>
<link rel='stylesheet' id='woocommerce-layout-rtl-css' href='https://lilac.local/wp-content/plugins/woocommerce/assets/css/woocommerce-layout-rtl.css?ver=9.8.2' media='all' />
<link rel='stylesheet' id='woocommerce-smallscreen-rtl-css' href='https://lilac.local/wp-content/plugins/woocommerce/assets/css/woocommerce-smallscreen-rtl.css?ver=9.8.2' media='only screen and (max-width: 768px)' />
<link rel='stylesheet' id='woocommerce-general-rtl-css' href='https://lilac.local/wp-content/plugins/woocommerce/assets/css/woocommerce-rtl.css?ver=9.8.2' media='all' />
<style id='woocommerce-inline-inline-css'>
.woocommerce form .form-row .required { visibility: visible; }
</style>
<link rel='stylesheet' id='learndash_quiz_front_css-rtl-css' href='//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_quiz_front-rtl.css?ver=4.20.2.1-1746019143' media='all' />
<link rel='stylesheet' id='dashicons-css' href='https://lilac.local/wp-includes/css/dashicons.css?ver=6.8' media='all' />
<link rel='stylesheet' id='learndash-rtl-css' href='//lilac.local/wp-content/plugins/sfwd-lms/src/assets/dist/css/styles-rtl.css?ver=4.20.2.1-1746019143' media='all' />
<link rel='stylesheet' id='jquery-dropdown-css-rtl-css' href='//lilac.local/wp-content/plugins/sfwd-lms/assets/css/jquery.dropdown.min-rtl.css?ver=4.20.2.1-1746019143' media='all' />
<link rel='stylesheet' id='learndash_lesson_video-css' href='//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_lesson_video.css?ver=4.20.2.1-1746019143' media='all' />
<link rel='stylesheet' id='learndash-admin-bar-css' href='https://lilac.local/wp-content/plugins/sfwd-lms/src/assets/dist/css/admin-bar/styles.css?ver=4.20.2.1' media='all' />
<link rel='stylesheet' id='learndash-admin-bar-rtl-css' href='https://lilac.local/wp-content/plugins/sfwd-lms/src/assets/dist/css/admin-bar/styles-rtl.css?ver=4.20.2.1' media='all' />
<link rel='stylesheet' id='brands-styles-css' href='https://lilac.local/wp-content/plugins/woocommerce/assets/css/brands.css?ver=9.8.2' media='all' />
<link rel='stylesheet' id='hello-elementor-theme-style-css' href='https://lilac.local/wp-content/themes/hello-elementor/theme.css?ver=3.3.0' media='all' />
<link rel='stylesheet' id='hello-elementor-child-style-css' href='https://lilac.local/wp-content/themes/hello-theme-child-master/style.css?ver=1.0.1' media='all' />
<link rel='stylesheet' id='user-widget-style-css' href='https://lilac.local/wp-content/themes/hello-theme-child-master/css/user-widget.css?ver=1.0.1' media='all' />
<link rel='stylesheet' id='ld-hints-style-css' href='https://lilac.local/wp-content/themes/hello-theme-child-master/learndash-hints/css/learndash-hints.css?ver=6.8' media='all' />
<link rel='stylesheet' id='hello-elementor-css' href='https://lilac.local/wp-content/themes/hello-elementor/style.css?ver=3.3.0' media='all' />
<link rel='stylesheet' id='hello-elementor-header-footer-css' href='https://lilac.local/wp-content/themes/hello-elementor/header-footer.css?ver=3.3.0' media='all' />
<link rel='stylesheet' id='elementor-frontend-css' href='https://lilac.local/wp-content/plugins/elementor/assets/css/frontend-rtl.css?ver=3.28.4' media='all' />
<link rel='stylesheet' id='elementor-post-11-css' href='https://lilac.local/wp-content/uploads/elementor/css/post-11.css?ver=1746016160' media='all' />
<link rel='stylesheet' id='lilac-toast-css' href='https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/css/toast.css?ver=1745373411' media='all' />
<link rel='stylesheet' id='lilac-progress-css' href='https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/css/progress.css?ver=1745374020' media='all' />
<link rel='stylesheet' id='learndash-front-rtl-css' href='//lilac.local/wp-content/plugins/sfwd-lms/themes/ld30/assets/css/learndash-rtl.css?ver=4.20.2.1-1746019143' media='all' />
<link rel='stylesheet' id='widget-image-css' href='https://lilac.local/wp-content/plugins/elementor/assets/css/widget-image-rtl.min.css?ver=3.28.4' media='all' />
<link rel='stylesheet' id='widget-nav-menu-css' href='https://lilac.local/wp-content/plugins/elementor-pro/assets/css/widget-nav-menu-rtl.min.css?ver=3.28.3' media='all' />
<link rel='stylesheet' id='widget-heading-css' href='https://lilac.local/wp-content/plugins/elementor/assets/css/widget-heading-rtl.min.css?ver=3.28.4' media='all' />
<link rel='stylesheet' id='widget-image-box-css' href='https://lilac.local/wp-content/plugins/elementor/assets/css/widget-image-box-rtl.min.css?ver=3.28.4' media='all' />
<link rel='stylesheet' id='elementor-post-1219-css' href='https://lilac.local/wp-content/uploads/elementor/css/post-1219.css?ver=1746016349' media='all' />
<link rel='stylesheet' id='elementor-post-891-css' href='https://lilac.local/wp-content/uploads/elementor/css/post-891.css?ver=1746016160' media='all' />
<link rel='stylesheet' id='elementor-post-1287-css' href='https://lilac.local/wp-content/uploads/elementor/css/post-1287.css?ver=1746016160' media='all' />
<link rel='stylesheet' id='elementor-gf-local-assistant-css' href='https://lilac.local/wp-content/uploads/elementor/google-fonts/css/assistant.css?ver=1745323175' media='all' />
<link rel='stylesheet' id='elementor-gf-local-poppins-css' href='https://lilac.local/wp-content/uploads/elementor/google-fonts/css/poppins.css?ver=1745408863' media='all' />
<script src="https://lilac.local/wp-includes/js/jquery/jquery.js?ver=3.7.1" id="jquery-core-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/jquery-migrate.js?ver=3.4.1" id="jquery-migrate-js"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/jquery-blockui/jquery.blockUI.js?ver=2.7.0-wc.9.8.2" id="jquery-blockui-js" defer data-wp-strategy="defer"></script>
<script id="wc-add-to-cart-js-extra">
var wc_add_to_cart_params = {"ajax_url":"\/wp-admin\/admin-ajax.php","wc_ajax_url":"\/?wc-ajax=%%endpoint%%","i18n_view_cart":"\u05de\u05e2\u05d1\u05e8 \u05dc\u05e1\u05dc \u05d4\u05e7\u05e0\u05d9\u05d5\u05ea","cart_url":"https:\/\/lilac.local\/cart\/","is_cart":"","cart_redirect_after_add":"no"};
</script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/frontend/add-to-cart.js?ver=9.8.2" id="wc-add-to-cart-js" defer data-wp-strategy="defer"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/js-cookie/js.cookie.js?ver=2.1.4-wc.9.8.2" id="js-cookie-js" defer data-wp-strategy="defer"></script>
<script id="woocommerce-js-extra">
var woocommerce_params = {"ajax_url":"\/wp-admin\/admin-ajax.php","wc_ajax_url":"\/?wc-ajax=%%endpoint%%","i18n_password_show":"\u05dc\u05d4\u05e6\u05d9\u05d2 \u05e1\u05d9\u05e1\u05de\u05d4","i18n_password_hide":"\u05dc\u05d4\u05e1\u05ea\u05d9\u05e8 \u05e1\u05d9\u05e1\u05de\u05d4"};
</script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/frontend/woocommerce.js?ver=9.8.2" id="woocommerce-js" defer data-wp-strategy="defer"></script>
<link rel="https://api.w.org/" href="https://lilac.local/wp-json/" /><link rel="alternate" title="JSON" type="application/json" href="https://lilac.local/wp-json/wp/v2/pages/1219" /><link rel="EditURI" type="application/rsd+xml" title="RSD" href="https://lilac.local/xmlrpc.php?rsd" />
<meta name="generator" content="WordPress 6.8" />
<meta name="generator" content="WooCommerce 9.8.2" />
<link rel="canonical" href="https://lilac.local/testlogin/" />
<link rel='shortlink' href='https://lilac.local/?p=1219' />
<link rel="alternate" title="oEmbed (JSON)" type="application/json+oembed" href="https://lilac.local/wp-json/oembed/1.0/embed?url=https%3A%2F%2Flilac.local%2Ftestlogin%2F" />
<link rel="alternate" title="oEmbed (XML)" type="text/xml+oembed" href="https://lilac.local/wp-json/oembed/1.0/embed?url=https%3A%2F%2Flilac.local%2Ftestlogin%2F&#038;format=xml" />
	<noscript><style>.woocommerce-product-gallery{ opacity: 1 !important; }</style></noscript>
	<meta name="generator" content="Elementor 3.28.4; features: e_font_icon_svg, additional_custom_breakpoints, e_local_google_fonts, e_element_cache; settings: css_print_method-external, google_font-enabled, font_display-swap">
			<style>
				.e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload),
				.e-con.e-parent:nth-of-type(n+4):not(.e-lazyloaded):not(.e-no-lazyload) * {
					background-image: none !important;
				}
				@media screen and (max-height: 1024px) {
					.e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload),
					.e-con.e-parent:nth-of-type(n+3):not(.e-lazyloaded):not(.e-no-lazyload) * {
						background-image: none !important;
					}
				}
				@media screen and (max-height: 640px) {
					.e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload),
					.e-con.e-parent:nth-of-type(n+2):not(.e-lazyloaded):not(.e-no-lazyload) * {
						background-image: none !important;
					}
				}
			</style>
			</head>
<body class="rtl wp-singular page-template page-template-page-templates page-template-page-test-login page-template-page-templatespage-test-login-php page page-id-1219 wp-embed-responsive wp-theme-hello-elementor wp-child-theme-hello-theme-child-master theme-hello-elementor woocommerce-no-js theme-default elementor-default elementor-kit-11 elementor-page elementor-page-1219">


<a class="skip-link screen-reader-text" href="#content">דלג לתוכן</a>

		<div data-elementor-type="header" data-elementor-id="891" class="elementor elementor-891 elementor-location-header" data-elementor-post-type="elementor_library">
			<div class="elementor-element elementor-element-f337361 e-con-full e-flex e-con e-parent" data-id="f337361" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
		<div class="elementor-element elementor-element-9061211 e-con-full e-flex e-con e-child" data-id="9061211" data-element_type="container">
		<div class="elementor-element elementor-element-3a954a0 e-con-full e-flex e-con e-child" data-id="3a954a0" data-element_type="container">
		<div class="elementor-element elementor-element-319e0d8 e-con-full e-flex e-con e-child" data-id="319e0d8" data-element_type="container">
				<div class="elementor-element elementor-element-c532764 elementor-widget elementor-widget-image" data-id="c532764" data-element_type="widget" data-widget_type="image.default">
				<div class="elementor-widget-container">
																<a href="https://lilac.local">
							<img fetchpriority="high" width="978" height="283" src="https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט.png" class="attachment-full size-full wp-image-1076" alt="" srcset="https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט.png 978w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-600x174.png 600w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-300x87.png 300w, https://lilac.local/wp-content/uploads/2025/02/לוגו-עם-טקסט-768x222.png 768w" sizes="(max-width: 978px) 100vw, 978px" />								</a>
															</div>
				</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-4e296e6 e-con-full e-flex e-con e-child" data-id="4e296e6" data-element_type="container">
				<div class="elementor-element elementor-element-c3fb80f elementor-nav-menu--dropdown-mobile elementor-nav-menu--stretch elementor-nav-menu__text-align-aside elementor-nav-menu--toggle elementor-nav-menu--burger elementor-widget elementor-widget-nav-menu" data-id="c3fb80f" data-element_type="widget" data-settings="{&quot;full_width&quot;:&quot;stretch&quot;,&quot;layout&quot;:&quot;horizontal&quot;,&quot;submenu_icon&quot;:{&quot;value&quot;:&quot;&lt;svg class=\&quot;e-font-icon-svg e-fas-caret-down\&quot; viewBox=\&quot;0 0 320 512\&quot; xmlns=\&quot;http:\/\/www.w3.org\/2000\/svg\&quot;&gt;&lt;path d=\&quot;M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z\&quot;&gt;&lt;\/path&gt;&lt;\/svg&gt;&quot;,&quot;library&quot;:&quot;fa-solid&quot;},&quot;toggle&quot;:&quot;burger&quot;}" data-widget_type="nav-menu.default">
				<div class="elementor-widget-container">
								<nav aria-label="תפריט" class="elementor-nav-menu--main elementor-nav-menu__container elementor-nav-menu--layout-horizontal e--pointer-none">
				<ul id="menu-1-c3fb80f" class="elementor-nav-menu"><li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1054"><a href="#" class="elementor-item elementor-item-anchor">מי אנחנו</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1055"><a href="#" class="elementor-item elementor-item-anchor">למה ללמוד אצלנו</a>
<ul class="sub-menu elementor-nav-menu--dropdown">
	<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1230"><a href="#" class="elementor-sub-item elementor-item-anchor">חינוך תעבורתי</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1056"><a href="#" class="elementor-item elementor-item-anchor">שאלות ותשובות</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1057"><a href="#" class="elementor-item elementor-item-anchor">טיפים למבחן התיאוריה</a>
<ul class="sub-menu elementor-nav-menu--dropdown">
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1228"><a href="https://lilac.local/tipes/6-%d7%98%d7%99%d7%a4%d7%99%d7%9d-%d7%97%d7%a9%d7%95%d7%91%d7%99%d7%9d-%d7%9c%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%9c%d7%9e%d7%91%d7%97%d7%9f-%d7%94%d7%aa/" class="elementor-sub-item">6 טיפים חשובים לחינוך תעבורתי – למבחן התיאוריה בבתי הספר</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1229"><a href="https://lilac.local/tipes/%d7%98%d7%99%d7%a4-7-%d7%98%d7%99%d7%a4-%d7%91%d7%98%d7%99%d7%97%d7%95%d7%aa%d7%99-%d7%97%d7%a9%d7%95%d7%91-%d7%9c%d7%9e%d7%91%d7%97%d7%9f/" class="elementor-sub-item">טיפ 7 – טיפ בטיחותי חשוב למבחן</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1227"><a href="https://lilac.local/tipes/%d7%a1%d7%a8%d7%98%d7%95%d7%9f-%d7%94%d7%a8%d7%a9%d7%9e%d7%94-%d7%9c%d7%9c%d7%90-%d7%a7%d7%95%d7%93-%d7%94%d7%98%d7%91%d7%94-%d7%90%d7%99%d7%9a-%d7%9c%d7%94%d7%a4%d7%a8%d7%99%d7%93-%d7%91%d7%99/" class="elementor-sub-item">סרטון – הרשמה ללא קוד הטבה: איך להפריד בין הרישום לפתיחת המנוי?</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1058"><a href="#" class="elementor-item elementor-item-anchor">תנו מילה טובה</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1059"><a href="#" class="elementor-item elementor-item-anchor">ספרי ההוצאה</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1192"><a href="https://lilac.local/%d7%aa%d7%a7%d7%a0%d7%95%d7%9f/" class="elementor-item">תקנון</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1184"><a href="https://lilac.local/%d7%99%d7%a6%d7%99%d7%a8%d7%aa-%d7%a7%d7%a9%d7%a8/" class="elementor-item">יצירת קשר</a></li>
<li class="ld-button menu-item menu-item-type-custom menu-item-object-custom menu-item-1115"><a href="https://lilac.local/wp-login.php?redirect_to=https%3A%2F%2Flilac.local%2Ftestlogin%2F" class="elementor-item">התחברות</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-1219 current_page_item menu-item-1231"><a href="https://lilac.local/testlogin/" aria-current="page" class="elementor-item elementor-item-active">test</a></li>
</ul>			</nav>
					<div class="elementor-menu-toggle" role="button" tabindex="0" aria-label="כפתור פתיחת תפריט" aria-expanded="false">
			<svg aria-hidden="true" role="presentation" class="elementor-menu-toggle__icon--open e-font-icon-svg e-eicon-menu-bar" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg"><path d="M104 333H896C929 333 958 304 958 271S929 208 896 208H104C71 208 42 237 42 271S71 333 104 333ZM104 583H896C929 583 958 554 958 521S929 458 896 458H104C71 458 42 487 42 521S71 583 104 583ZM104 833H896C929 833 958 804 958 771S929 708 896 708H104C71 708 42 737 42 771S71 833 104 833Z"></path></svg><svg aria-hidden="true" role="presentation" class="elementor-menu-toggle__icon--close e-font-icon-svg e-eicon-close" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg"><path d="M742 167L500 408 258 167C246 154 233 150 217 150 196 150 179 158 167 167 154 179 150 196 150 212 150 229 154 242 171 254L408 500 167 742C138 771 138 800 167 829 196 858 225 858 254 829L496 587 738 829C750 842 767 846 783 846 800 846 817 842 829 829 842 817 846 804 846 783 846 767 842 750 829 737L588 500 833 258C863 229 863 200 833 171 804 137 775 137 742 167Z"></path></svg>		</div>
					<nav class="elementor-nav-menu--dropdown elementor-nav-menu__container" aria-hidden="true">
				<ul id="menu-2-c3fb80f" class="elementor-nav-menu"><li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1054"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">מי אנחנו</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1055"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">למה ללמוד אצלנו</a>
<ul class="sub-menu elementor-nav-menu--dropdown">
	<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1230"><a href="#" class="elementor-sub-item elementor-item-anchor" tabindex="-1">חינוך תעבורתי</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1056"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">שאלות ותשובות</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-1057"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">טיפים למבחן התיאוריה</a>
<ul class="sub-menu elementor-nav-menu--dropdown">
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1228"><a href="https://lilac.local/tipes/6-%d7%98%d7%99%d7%a4%d7%99%d7%9d-%d7%97%d7%a9%d7%95%d7%91%d7%99%d7%9d-%d7%9c%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%9c%d7%9e%d7%91%d7%97%d7%9f-%d7%94%d7%aa/" class="elementor-sub-item" tabindex="-1">6 טיפים חשובים לחינוך תעבורתי – למבחן התיאוריה בבתי הספר</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1229"><a href="https://lilac.local/tipes/%d7%98%d7%99%d7%a4-7-%d7%98%d7%99%d7%a4-%d7%91%d7%98%d7%99%d7%97%d7%95%d7%aa%d7%99-%d7%97%d7%a9%d7%95%d7%91-%d7%9c%d7%9e%d7%91%d7%97%d7%9f/" class="elementor-sub-item" tabindex="-1">טיפ 7 – טיפ בטיחותי חשוב למבחן</a></li>
	<li class="menu-item menu-item-type-post_type menu-item-object-tipes menu-item-1227"><a href="https://lilac.local/tipes/%d7%a1%d7%a8%d7%98%d7%95%d7%9f-%d7%94%d7%a8%d7%a9%d7%9e%d7%94-%d7%9c%d7%9c%d7%90-%d7%a7%d7%95%d7%93-%d7%94%d7%98%d7%91%d7%94-%d7%90%d7%99%d7%9a-%d7%9c%d7%94%d7%a4%d7%a8%d7%99%d7%93-%d7%91%d7%99/" class="elementor-sub-item" tabindex="-1">סרטון – הרשמה ללא קוד הטבה: איך להפריד בין הרישום לפתיחת המנוי?</a></li>
</ul>
</li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1058"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">תנו מילה טובה</a></li>
<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-1059"><a href="#" class="elementor-item elementor-item-anchor" tabindex="-1">ספרי ההוצאה</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1192"><a href="https://lilac.local/%d7%aa%d7%a7%d7%a0%d7%95%d7%9f/" class="elementor-item" tabindex="-1">תקנון</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1184"><a href="https://lilac.local/%d7%99%d7%a6%d7%99%d7%a8%d7%aa-%d7%a7%d7%a9%d7%a8/" class="elementor-item" tabindex="-1">יצירת קשר</a></li>
<li class="ld-button menu-item menu-item-type-custom menu-item-object-custom menu-item-1115"><a href="https://lilac.local/wp-login.php?redirect_to=https%3A%2F%2Flilac.local%2Ftestlogin%2F" class="elementor-item" tabindex="-1">התחברות</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page current-menu-item page_item page-item-1219 current_page_item menu-item-1231"><a href="https://lilac.local/testlogin/" aria-current="page" class="elementor-item elementor-item-active" tabindex="-1">test</a></li>
</ul>			</nav>
						</div>
				</div>
				</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-87e3881 e-con-full e-flex e-con e-parent" data-id="87e3881" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
				<div class="elementor-element elementor-element-103af19 elementor-widget__width-initial elementor-widget elementor-widget-shortcode" data-id="103af19" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">        <div class="lilac-user-widget lilac-user-logged-out">
            <div class="lilac-login-buttons">
                <a href="https://lilac.local/login/" class="lilac-login-btn">
                    התחברות
                </a>
                
                <div class="lilac-register-links">
                    <a href="https://lilac.local/login/?type=school" class="lilac-register-btn">
                        הרשמה לתלמידי י'
                    </a>
                    <a href="https://lilac.local/login/?type=private" class="lilac-register-btn">
                        הרשמה ללקוחות פרטיים
                    </a>
                </div>
            </div>
        </div>
        </div>
						</div>
				</div>
				</div>
				</div>
		
<div class="lilac-test-login-page">
    <div class="lilac-page-header">
        <h1>בדיקת טפסי הרשמה</h1>
        <div class="lilac-page-content">
            		<div data-elementor-type="wp-page" data-elementor-id="1219" class="elementor elementor-1219" data-elementor-post-type="page">
				<div class="elementor-element elementor-element-b18e703 e-flex e-con-boxed e-con e-parent" data-id="b18e703" data-element_type="container">
					<div class="e-con-inner">
				<div class="elementor-element elementor-element-fa90630 elementor-widget elementor-widget-text-editor" data-id="fa90630" data-element_type="widget" data-widget_type="text-editor.default">
				<div class="elementor-widget-container">
									<p>בדיקת התחברות לקורס</p><p><a href="https://lilac.local/lessons/%d7%a4%d7%a8%d7%a7-01-%d7%aa%d7%95%d7%a8%d7%aa-%d7%94%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%94%d7%aa%d7%a2%d7%91%d7%a8%d7%95%d7%aa%d7%99-%d7%a4%d7%a8%d7%a7-%d7%9e%d7%91%d7%95%d7%90/">1</a></p><p><a href="/courses/driveing-students/lessons/%d7%a4%d7%a8%d7%a7-01-%d7%aa%d7%95%d7%a8%d7%aa-%d7%94%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%94%d7%aa%d7%a2%d7%91%d7%a8%d7%95%d7%aa%d7%99-%d7%a4%d7%a8%d7%a7-%d7%9e%d7%91%d7%95%d7%90/topics/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d/">חינוך תעבורתי</a>  <a href="https://lilac.local/courses/driveing-students/lessons/%d7%a4%d7%a8%d7%a7-01-%d7%aa%d7%95%d7%a8%d7%aa-%d7%94%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%94%d7%aa%d7%a2%d7%91%d7%a8%d7%95%d7%aa%d7%99-%d7%a4%d7%a8%d7%a7-%d7%9e%d7%91%d7%95%d7%90/">פרק 01 – תורת החינוך התעברותי ( פרק מבוא )</a>  <a href="https://lilac.local/courses/driveing-students/lessons/%d7%a4%d7%a8%d7%a7-01-%d7%aa%d7%95%d7%a8%d7%aa-%d7%94%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%94%d7%aa%d7%a2%d7%91%d7%a8%d7%95%d7%aa%d7%99-%d7%a4%d7%a8%d7%a7-%d7%9e%d7%91%d7%95%d7%90/topics/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d/">המרחב התעבורתי – הגדרה ומאפיינים</a></p><p><a href="/courses/driveing-students/lessons/%d7%a4%d7%a8%d7%a7-01-%d7%aa%d7%95%d7%a8%d7%aa-%d7%94%d7%97%d7%99%d7%a0%d7%95%d7%9a-%d7%94%d7%aa%d7%a2%d7%91%d7%a8%d7%95%d7%aa%d7%99-%d7%a4%d7%a8%d7%a7-%d7%9e%d7%91%d7%95%d7%90/quizzes/%d7%94%d7%9e%d7%a8%d7%97%d7%91-%d7%94%d7%aa%d7%a2%d7%91%d7%95%d7%a8%d7%aa%d7%99-%d7%94%d7%92%d7%93%d7%a8%d7%94-%d7%95%d7%9e%d7%90%d7%a4%d7%99%d7%99%d7%a0%d7%99%d7%9d/">המרחב התעבורתי – הגדרה ומאפיינים</a></p><p><a href="https://lilac.local/groups/%d7%9e%d7%a0%d7%95%d7%99-%d7%91%d7%9e%d7%a1%d7%9c%d7%95%d7%9c-%d7%a4%d7%a8%d7%98%d7%99/">בדיקת מבחן פתוח</a></p><p><a href="/courses/client-course/">קורס ללקוחות פרטיים</a></p>								</div>
				</div>
					</div>
				</div>
		<div class="elementor-element elementor-element-8f30aad e-flex e-con-boxed e-con e-parent" data-id="8f30aad" data-element_type="container">
					<div class="e-con-inner">
		<div class="elementor-element elementor-element-c7a7a00 e-con-full e-flex e-con e-child" data-id="c7a7a00" data-element_type="container">
				<div class="elementor-element elementor-element-2792c6a elementor-widget elementor-widget-heading" data-id="2792c6a" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">תלמידים</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-4a3695a elementor-widget elementor-widget-shortcode" data-id="4a3695a" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode"><form method="post"><p><label>הקלד את קוד הגישה:</label><br><input type="text" name="access_code" required></p><input type="hidden" name="access_code_action" value="1"><p><button type="submit">שלח</button></p></form></div>
						</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-6c68044 e-con-full e-flex e-con e-child" data-id="6c68044" data-element_type="container">
				<div class="elementor-element elementor-element-9229f8c elementor-widget elementor-widget-heading" data-id="9229f8c" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">פרטי</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-aa9fcb9 elementor-widget elementor-widget-image-box" data-id="aa9fcb9" data-element_type="widget" data-widget_type="image-box.default">
				<div class="elementor-widget-container">
					<div class="elementor-image-box-wrapper"><div class="elementor-image-box-content"><p class="elementor-image-box-description">הערה כללית: שני המסלולים באתר נפרדים לגמרי. תלמיד יכול לרכוש במקביל מנוי במסלול חינוך תעבורתי וגם מנוי במסלול פרטי באותו שם משתמש. 

שלב 1: כניסה לאתר ובחירת מסלול לימוד
התלמיד נכנס לאתר ומגיע לעמוד הראשי.
נכנס למסלול לומד תיאוריה עצמאי/פרטי (לא לתלמיד כיתה י')
בעמוד הראשי עליו לבחור את המוצר המתאים לו:
ערכת תיאוריה  (המקנה תרגול חינמי באתר לחודש)
קורס תיאוריה מקוון (שיעורים מוקלטים + תרגול)
תרגול בלבד (אולי יבוטל)


בדף הראשי לפני בחירה התלמיד יכול להתרשם מתוכן חינמי בהתאם למסלולי הלימוד:
פרק חינם מתוך ספר הלימוד + .מבחן נושא חינמי עם רמזים באותו נושא. 
קורס - שיעור דוגמה מתוך הקורס + תרגול באותו שיעור. 
תרגול - מבחן תרגול או נושא עם רמזים
שלב 2: תהליך הרשמה ורכישת מנוי 

אופציה 1: רכישת ערכת ספרים (המקנה תרגול חינמי לחודש)

התלמיד בוחר ערכת תיאוריה ומועבר לעמוד הרכישה.
ממלא טופס הרשמה:</p></div></div>				</div>
				</div>
				</div>
					</div>
				</div>
		<div class="elementor-element elementor-element-155ea6a e-flex e-con-boxed e-con e-parent" data-id="155ea6a" data-element_type="container">
					<div class="e-con-inner">
		<div class="elementor-element elementor-element-71e8582 e-con-full e-flex e-con e-child" data-id="71e8582" data-element_type="container">
				<div class="elementor-element elementor-element-b9528db elementor-widget elementor-widget-heading" data-id="b9528db" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">תלמידים</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-5ecc552 elementor-widget elementor-widget-shortcode" data-id="5ecc552" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode"><p>שימו לב! ניתן להירשם למנוי רק לאחר קבלת הספרים. קוד ההטבה נמצא בתוך הספר.</p><form method="post"><input type="hidden" name="group_id" value="1320"><input type="hidden" name="course_id" value="1292"><p><label>שם פרטי:</label><br><input type="text" name="first_name" required></p><p><label>שם משפחה:</label><br><input type="text" name="last_name" required></p><p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p><p><label>וודא טלפון נייד:</label><br><input type="text" name="phone_confirm" required></p><p><label>משלוח:</label><br><select name="delivery"><option value="pickup">איסוף עצמי</option><option value="shipping">משלוח</option></select></p><div id="shipping_address" style="display:none;"><p><label>עיר:</label><br><input type="text" name="city"></p><p><label>רחוב:</label><br><input type="text" name="street"></p><p><label>טלפון לשליח:</label><br><input type="text" name="delivery_phone"></p></div><p><label>אמצעי תשלום:</label><br><select name="payment_method"><option value="bit">ביט</option><option value="credit">אשראי</option></select></p><p><button type="submit" name="purchase_submit">המשך לתשלום</button></p></form></div>
						</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-365197a e-con-full e-flex e-con e-child" data-id="365197a" data-element_type="container">
				<div class="elementor-element elementor-element-e39d6f1 elementor-widget elementor-widget-heading" data-id="e39d6f1" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">פרטי</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-16bfdb4 elementor-widget elementor-widget-shortcode" data-id="16bfdb4" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">
course-purchase.php – Course Purchase form 

<!-- debug: course_purchase_render for course_id=1292 --><form method="post"><input type="hidden" name="course_id" value="1292"><input type="hidden" name="group_id" value="1320"><!-- debug: course_purchase_render group_id=1320 --><p><label>קוד קופון (אופציונלי):</label><br><input type="text" name="coupon_code"></p><p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p><p><label>וידוא טלפון:</label><br><input type="text" name="phone_confirm" required></p><p><label>תעודת זהות:</label><br><input type="text" name="id_number" required></p><p><label>וידוא תעודת זהות:</label><br><input type="text" name="id_number_confirm" required></p><p><label>תקופת מנוי:</label><br><select name="period"><option value="1">1 חודש</option><option value="12">12 חודשים</option></select></p><p><label>אמצעי תשלום:</label><br><select name="payment_method"><option value="bit">ביט</option><option value="credit">אשראי</option><option value="demo">דמו חינם</option></select></p><p><button type="submit" name="course_purchase_submit">המשך לתשלום</button></p></form></div>
						</div>
				</div>
				</div>
					</div>
				</div>
		<div class="elementor-element elementor-element-a747a01 e-flex e-con-boxed e-con e-parent" data-id="a747a01" data-element_type="container">
					<div class="e-con-inner">
		<div class="elementor-element elementor-element-857b9c5 e-con-full e-flex e-con e-child" data-id="857b9c5" data-element_type="container">
				<div class="elementor-element elementor-element-7a67f6b elementor-widget elementor-widget-heading" data-id="7a67f6b" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">הרשמה דרך קוד</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-d1eda65 elementor-widget elementor-widget-shortcode" data-id="d1eda65" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">
code-subscribe.php – Subscription via Code form <form method="post"><p><label>קוד מנוי:</label><br><input type="text" name="subscription_code" required></p><p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p><p><label>וידוא טלפון:</label><br><input type="text" name="phone_confirm" required></p><p><label>תעודת זהות:</label><br><input type="text" name="id_number" required></p><p><label>וידוא תעודת זהות:</label><br><input type="text" name="id_number_confirm" required></p><p><label>בחירת מסלול:</label><br><select name="track"><option value="standard">סטנדרטי</option><option value="premium">פרימיום</option></select></p><p><button type="submit" name="code_subscribe_submit">הרשמה</button></p></form></div>
						</div>
				</div>
				</div>
		<div class="elementor-element elementor-element-b76dde3 e-con-full e-flex e-con e-child" data-id="b76dde3" data-element_type="container">
				<div class="elementor-element elementor-element-3c0a1ab elementor-widget elementor-widget-heading" data-id="3c0a1ab" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">טופס לבדיקה בלבד</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-f78904f elementor-widget elementor-widget-shortcode" data-id="f78904f" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">
practice-purchase.php – Practice-only form <form method="post"><p><label>שם פרטי:</label><br><input type="text" name="first_name" required></p><p><label>שם משפחה:</label><br><input type="text" name="last_name" required></p><p><label>טלפון נייד:</label><br><input type="text" name="phone" required></p><p><label>וודא טלפון:</label><br><input type="text" name="phone_confirm" required></p><p><label>מסלול תרגול:</label><br><select name="track"><option value="quizzes">חידונים בלבד</option><option value="practice_tests">מבחנים לתרגול</option></select></p><p><label>תקופת מנוי:</label><br><select name="period"><option value="1">1 חודש</option><option value="12">12 חודשים</option></select></p><p><label>אמצעי תשלום:</label><br><select name="payment_method"><option value="bit">ביט</option><option value="credit">אשראי</option></select></p><p><button type="submit" name="practice_purchase_submit">המשך לתשלום</button></p></form></div>
						</div>
				</div>
				</div>
					</div>
				</div>
		<div class="elementor-element elementor-element-dd3bd75 e-flex e-con-boxed e-con e-parent" data-id="dd3bd75" data-element_type="container">
					<div class="e-con-inner">
				<div class="elementor-element elementor-element-eef0bfd elementor-widget elementor-widget-heading" data-id="eef0bfd" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">בדיקת מבחן</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-e61956c elementor-widget elementor-widget-shortcode" data-id="e61956c" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">ld_quiz quiz_id="1309"]
		<div class="wpProQuiz_content" id="wpProQuiz_7" data-quiz-meta="{&quot;quiz_pro_id&quot;:7,&quot;quiz_post_id&quot;:1309}">
			<div class="wpProQuiz_spinner" style="display:none">
				<div></div>
			</div>
			<div class='wpProQuiz_description'><p>לפניך שאלות לפי נושאי לימוד למבחן התיאוריה. תשובה שגויה מסומנת באדום.<br />
באתר ישנו מנגנון ייחודי של קבלת רמזים לכל שאלה. ללמידה נכונה העזרו במנגון הרמזים. לתיקון התשובה לחצו: קבל רמז!</p>
<p><img decoding="async" src="https://lilac.local/wp-content/uploads/2025/02/noPic.png" alt="" width="290" height="180" /></p>
<p><a role="button"><br />
קח רמז<br />
</a></p>
</div><div style="display: none;" class="wpProQuiz_time_limit">
	<div class="time">
		זמן מוגבל: <span>0</span>	</div>
	<div class="wpProQuiz_progress"></div>
</div>
<div class="wpProQuiz_checkPage" style="display: none;">
	<h4 class="wpProQuiz_header">
	תקציר המבחן	</h4>
	<p><span>0</span> מתוך 9 שאלות הושלמו</p>	<p>שאלות:</p>
	<div class="wpProQuiz_reviewSummary"></div>

	
	<input type="button" name="endQuizSummary" value="סיים מבחן" class="wpProQuiz_button" /> </div>
<div class="wpProQuiz_infopage" style="display: none;">
	<h4>מידע</h4>
		<input type="button" name="endInfopage" value="סיים מבחן" class="wpProQuiz_button" /> </div>
<div class="wpProQuiz_text">
		<div>
		<input class="wpProQuiz_button" type="button" 
		value="התחל מבחן" name="startQuiz" />	</div>
</div>
<div style="display: none;" class="wpProQuiz_lock">		
	<p>כבר השלמת את המבחן בעבר. לכן אינך יכול להתחיל אותו שוב.</p></div>
<div style="display: none;" class="wpProQuiz_loadQuiz">
	<p>
		המבחן נטען...	</p>
</div>
<div style="display: none;" class="wpProQuiz_startOnlyRegisteredUser">
	<p>עליך להתחבר או להירשם כדי להתחיל את המבחן.</p></div>
<div style="display: none;" class="wpProQuiz_prerequisite">
	<p>אתה חייב להשלים קודם את הבאים: <span></span></p></div>
<div style="display: none;" class="wpProQuiz_sending">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p>
		<div>
		מבחןהושלם. התוצאות נרשמות.		</div>
		<div>
			<dd class="course_progress">
				<div class="course_progress_blue sending_progress_bar" style="width: 0%;">
				</div>
			</dd>
		</div>
	</p>
</div>

<div style="display: none;" class="wpProQuiz_results">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p><span class="wpProQuiz_correct_answer">0</span> מתוך <span>9</span> שאלות נענו נכון</p>		<p class="wpProQuiz_quiz_time">
		הזמן שלך: <span></span>		</p>
			<p class="wpProQuiz_time_limit_expired" style="display: none;">
	זמן חלף	</p>

			<p class="wpProQuiz_points">
		השגת <span>0</span> מתוך <span>0</span> נקודה(ות), (<span>0</span>)		</p>
		<p class="wpProQuiz_graded_points" style="display: none;">
		נקודה(ות) שקיבלת: <span>0</span> מתוך <span>0</span>, (<span>0</span>)		<br />
		<span>0</span> שאלות פתוחות בהמתנה (נקודה(ות) אפשרית(יות): <span>0</span>)		<br />
		</p>
		
	<div class="wpProQuiz_catOverview" style="display:none;">
		<h4>
		קטגוריות		</h4>

		<div style="margin-top: 10px;">
			<ol>
							<li data-category_id="0">
					<span class="wpProQuiz_catName">כללי</span>
					<span class="wpProQuiz_catPercent">0%</span>
				</li>
							</ol>
		</div>
	</div>
	<div>
		<ul class="wpProQuiz_resultsList">
							<li style="display: none;">
					<div>
											</div>
				</li>
					</ul>
	</div>
		<div class="ld-quiz-actions" style="margin: 10px 0px;">
				<div class='quiz_continue_link
				'>

		</div>
					<input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz"
					value="התחל מבחן מחדש"/>						<input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion"
					value="הצג שאלות" />					</div>
</div>
<div class="wpProQuiz_reviewDiv" style="display: none;">
	<div class="wpProQuiz_reviewQuestion">
	<ol>
					<li>1</li>
					<li>2</li>
					<li>3</li>
					<li>4</li>
					<li>5</li>
					<li>6</li>
					<li>7</li>
					<li>8</li>
					<li>9</li>
			</ol>
	<div style="display: none;"></div>
</div>
<div class="wpProQuiz_reviewLegend">
	<ol>
		<li class="learndash-quiz-review-legend-item-current">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewQuestion_Target"></span>
			<span class="wpProQuiz_reviewText">הנוכחי</span>
		</li>
		<li class="learndash-quiz-review-legend-item-review">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Review"></span>
			<span class="wpProQuiz_reviewText">ביקורת</span>
		</li>
		<li class="learndash-quiz-review-legend-item-answered">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Answer"></span>
			<span class="wpProQuiz_reviewText">נענו</span>
		</li>
		<li class="learndash-quiz-review-legend-item-correct">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerCorrect"></span>
			<span class="wpProQuiz_reviewText">נכון</span>
		</li>
		<li class="learndash-quiz-review-legend-item-incorrect">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerIncorrect"></span>
			<span class="wpProQuiz_reviewText">לא נכון</span>
		</li>
	</ol>
	<div style="clear: both;"></div>
</div>
<div class="wpProQuiz_reviewButtons">
			<input type="button" name="review" value="סקור שאלה" class="wpProQuiz_button2" style="float: left; display: block;"> 				<div style="clear: both;"></div>
	</div>
</div>
<div class="wpProQuiz_quizAnker" style="display: none;"></div>
<div style="display: none;" class="wpProQuiz_quiz">
	<ol class="wpProQuiz_list">
					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:30,&quot;question_post_id&quot;:1310}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>1</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>1</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מיהו "עובר דרך"?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="30"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_30"
													value="1"> המשתמש בכביש לרכיבה על אופניים בלבד.
										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_30"
													value="2"> המשתמש בדרך לנסיעה ברכב מנועי בלבד.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_30"
													value="3"> המשתמש בדרך לעמידה או לריצה בלבד.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_30"
													value="4"> המשתמש בדרך לנסיעה, להליכה, לעמידה או לכל מטרה אחרת.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. <span class="highlight-hint">עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.<br /></span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br /><button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:31,&quot;question_post_id&quot;:1311}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>2</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>2</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהם מרכיבי ה"מרחב התעבורתי"?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="31"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_31"
													value="1"> כל מה שנמצא בעולם, כולל הרכוש ומזג האוויר שבתוך הבתים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_31"
													value="2"> כל מה שנמצא בדרך וכל מי שמשתמש בדרך: פני הדרך, עוברי דרך, כלי הרכב ותנאי הסביבה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_31"
													value="3"> מרכיבים ששייכים לעולם התעבורה בלבד: עוברי דרך וכלי רכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_31"
													value="4"> מרכיבים ששייכים למרחב בלבד: פני הדרך ותנאי הסביבה.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:32,&quot;question_post_id&quot;:1312}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>3</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>3</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>במרחב התעבורתי מתקיימים מפגשים רבים בין עוברי דרך בהם עליהם להתחשב אחד בשני. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="32"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_32"
													value="1"> לא נכון. אם עוברי הדרך ינהגו לפי הוראות החוק לא יתקיימו נקודות מגע ביניהם.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_32"
													value="2"> לא נכון, בכל מקרה במרחב התעבורתי אין מפגשים בין עוברי דרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_32"
													value="3"> לא נכון, כל אחד ממשתמשי הדרך נע בנתיב שלו ולא נפגש עם אחרים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_32"
													value="4"> נכון, מפגשים מתקיימים במרחב התעבורתי בכל שעות היום ובכל מקום בדרך.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. <span class="highlight-hint">ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</span></p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:33,&quot;question_post_id&quot;:1313}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>4</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>4</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>הנהיגה היא משימה מורכבת. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="33"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_33"
													value="1"> לא נכון. רק כשיש עומס בדרכים לפעמים קשה לנהוג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_33"
													value="2"> לא נכון. רק נהגי משאיות כבדות מתעייפים בנהיגה בגלל משאם הכבד.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_33"
													value="3"> נכון. מטלת הנהיגה דורשת ביצוע מספר מטלות במקביל ובזמן קצר וזה מורכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_33"
													value="4"> נכון. אבל רק בגלל שלנהגים אין סבלנות והם לא מעניקים זכות קדימה כנדרש בחוק.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p><span class="highlight-hint">הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</span></p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:34,&quot;question_post_id&quot;:1314}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>5</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>5</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם תלמיד נהיגה הוא חלק מהמרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="34"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_34"
													value="1"> לא. רק אחרי שיקבל רישיון נהיגה יהפוך תלמיד נהיגה לחלק מהמרחב התעבורתי.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_34"
													value="2"> לא. המרחב התעבורתי לא כולל תלמידי נהיגה הנוהגים ברכב ללימוד נהיגה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_34"
													value="3"> כן, אבל רק אם הוא נוהג בדרך בינעירונית.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_34"
													value="4"> כן, כל מי שנמצא בדרך ומשתמש בה הוא חלק מהמרחב התעבורתי.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:35,&quot;question_post_id&quot;:1315}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>6</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>6</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם יש סיכונים במרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="35"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_35"
													value="1"> ממש לא.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_35"
													value="2"> כן, רק בדרכים בינעירוניות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_35"
													value="3"> כן, המרחב התעבורתי מאופיין באי ודאות ובסכנות רבות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_35"
													value="4"> כן, רק בדרכים עירוניות צפופות.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p><span class="highlight-hint">נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</span></p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:36,&quot;question_post_id&quot;:1316}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>7</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>7</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין עובר דרך לסביבה הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="36"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_36"
													value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_36"
													value="2"> מפגש בין תמרור להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_36"
													value="3"> מפגש בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_36"
													value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:37,&quot;question_post_id&quot;:1317}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>8</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>8</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין הדרך לעובר דרך הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="37"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_37"
													value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_37"
													value="2"> מפגש בשעות הלילה בין נהג להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_37"
													value="3"> מפגש ביום גשום בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_37"
													value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:38,&quot;question_post_id&quot;:1318}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>9</span> מתוך <span>9</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>9</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהו "מרחב תעבורתי"?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="38"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_38"
													value="1"> מרחב תעבורתי הוא המרחב שבו כלי הרכב נעים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_38"
													value="2"> מרחב תעבורתי כולל את כל בני האדם המשתמשים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_38"
													value="3"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_7_38"
													value="4"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך, מלבד עגלות שאין להן מנוע.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

			</ol>
	</div>
		</div>
		
</div>
						</div>
				</div>
				<div class="elementor-element elementor-element-8290a50 elementor-widget elementor-widget-heading" data-id="8290a50" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<h4 class="elementor-heading-title elementor-size-default">בדיקת מבחן 2</h4>				</div>
				</div>
				<div class="elementor-element elementor-element-2a19fa2 elementor-widget elementor-widget-shortcode" data-id="2a19fa2" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode"><div class="learndash user_has_no_access"  id="learndash_post_1367"><div class="learndash-wrapper">

<div class="ld-tabs ld-tab-count-1">
	
	<div class="ld-tabs-content">
		
			<div
				aria-labelledby="ld-content-tab-1219"
				class="ld-tab-content ld-visible"
				id="ld-tab-content-1219"
				role="tabpanel"
				tabindex="0"
			>
											</div>

			
	</div> <!--/.ld-tabs-content-->

</div> <!--/.ld-tabs-->
		<div class="wpProQuiz_content" id="wpProQuiz_9" data-quiz-meta="{&quot;quiz_pro_id&quot;:9,&quot;quiz_post_id&quot;:1367}">
			<div class="wpProQuiz_spinner" style="display:none">
				<div></div>
			</div>
			<div class='wpProQuiz_description'><p>לפניך שאלות לפי נושאי לימוד למבחן התיאוריה. תשובה שגויה מסומנת באדום.<br />
באתר ישנו מנגנון ייחודי של קבלת רמזים לכל שאלה. ללמידה נכונה העזרו במנגון הרמזים. לתיקון התשובה לחצו: קבל רמז!</p>
<p><img decoding="async" src="https://lilac.local/wp-content/uploads/2025/02/noPic.png" alt="" width="290" height="180" /></p>
<p><a role="button"><br />
קח רמז<br />
</a></p>
</div><div style="display: none;" class="wpProQuiz_time_limit">
	<div class="time">
		זמן מוגבל: <span>0</span>	</div>
	<div class="wpProQuiz_progress"></div>
</div>
<div class="wpProQuiz_checkPage" style="display: none;">
	<h4 class="wpProQuiz_header">
	תקציר המבחן	</h4>
	<p><span>0</span> מתוך 8 שאלות הושלמו</p>	<p>שאלות:</p>
	<div class="wpProQuiz_reviewSummary"></div>

	
	<input type="button" name="endQuizSummary" value="סיים מבחן" class="wpProQuiz_button" /> </div>
<div class="wpProQuiz_infopage" style="display: none;">
	<h4>מידע</h4>
		<input type="button" name="endInfopage" value="סיים מבחן" class="wpProQuiz_button" /> </div>
<div class="wpProQuiz_text">
		<div>
		<input class="wpProQuiz_button" type="button" 
		value="התחל מבחן" name="startQuiz" />	</div>
</div>
<div style="display: none;" class="wpProQuiz_lock">		
	<p>כבר השלמת את המבחן בעבר. לכן אינך יכול להתחיל אותו שוב.</p></div>
<div style="display: none;" class="wpProQuiz_loadQuiz">
	<p>
		המבחן נטען&#8230;	</p>
</div>
<div style="display: none;" class="wpProQuiz_startOnlyRegisteredUser">
	<p>עליך להתחבר או להירשם כדי להתחיל את המבחן.</p></div>
<div style="display: none;" class="wpProQuiz_prerequisite">
	<p>אתה חייב להשלים קודם את הבאים: <span></span></p></div>
<div style="display: none;" class="wpProQuiz_sending">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p>
		<div>
		מבחןהושלם. התוצאות נרשמות.		</div>
		<div>
			<dd class="course_progress">
				<div class="course_progress_blue sending_progress_bar" style="width: 0%;">
				</div>
			</dd>
		</div>
	</p>
</div>

<div style="display: none;" class="wpProQuiz_results">
	<h4 class="wpProQuiz_header">תוצאות</h4>
	<p><span class="wpProQuiz_correct_answer">0</span> מתוך <span>8</span> שאלות נענו נכון</p>		<p class="wpProQuiz_quiz_time">
		הזמן שלך: <span></span>		</p>
			<p class="wpProQuiz_time_limit_expired" style="display: none;">
	זמן חלף	</p>

			<p class="wpProQuiz_points">
		השגת <span>0</span> מתוך <span>0</span> נקודה(ות), (<span>0</span>)		</p>
		<p class="wpProQuiz_graded_points" style="display: none;">
		נקודה(ות) שקיבלת: <span>0</span> מתוך <span>0</span>, (<span>0</span>)		<br />
		<span>0</span> שאלות פתוחות בהמתנה (נקודה(ות) אפשרית(יות): <span>0</span>)		<br />
		</p>
		
	<div class="wpProQuiz_catOverview" style="display:none;">
		<h4>
		קטגוריות		</h4>

		<div style="margin-top: 10px;">
			<ol>
							<li data-category_id="0">
					<span class="wpProQuiz_catName">כללי</span>
					<span class="wpProQuiz_catPercent">0%</span>
				</li>
							</ol>
		</div>
	</div>
	<div>
		<ul class="wpProQuiz_resultsList">
							<li style="display: none;">
					<div>
											</div>
				</li>
					</ul>
	</div>
		<div class="ld-quiz-actions" style="margin: 10px 0px;">
				<div class='quiz_continue_link
				'>

		</div>
					<input class="wpProQuiz_button wpProQuiz_button_restartQuiz" type="button" name="restartQuiz"
					value="התחל מבחן מחדש"/>						<input class="wpProQuiz_button wpProQuiz_button_reShowQuestion" type="button" name="reShowQuestion"
					value="הצג שאלות" />					</div>
</div>
<div class="wpProQuiz_reviewDiv" style="display: none;">
	<div class="wpProQuiz_reviewQuestion">
	<ol>
					<li>1</li>
					<li>2</li>
					<li>3</li>
					<li>4</li>
					<li>5</li>
					<li>6</li>
					<li>7</li>
					<li>8</li>
			</ol>
	<div style="display: none;"></div>
</div>
<div class="wpProQuiz_reviewLegend">
	<ol>
		<li class="learndash-quiz-review-legend-item-current">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewQuestion_Target"></span>
			<span class="wpProQuiz_reviewText">הנוכחי</span>
		</li>
		<li class="learndash-quiz-review-legend-item-review">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Review"></span>
			<span class="wpProQuiz_reviewText">ביקורת</span>
		</li>
		<li class="learndash-quiz-review-legend-item-answered">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_Answer"></span>
			<span class="wpProQuiz_reviewText">נענו</span>
		</li>
		<li class="learndash-quiz-review-legend-item-correct">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerCorrect"></span>
			<span class="wpProQuiz_reviewText">נכון</span>
		</li>
		<li class="learndash-quiz-review-legend-item-incorrect">
			<span class="wpProQuiz_reviewColor wpProQuiz_reviewColor_AnswerIncorrect"></span>
			<span class="wpProQuiz_reviewText">לא נכון</span>
		</li>
	</ol>
	<div style="clear: both;"></div>
</div>
<div class="wpProQuiz_reviewButtons">
			<input type="button" name="review" value="סקור שאלה" class="wpProQuiz_button2" style="float: left; display: block;"> 				<div style="clear: both;"></div>
	</div>
</div>
<div class="wpProQuiz_quizAnker" style="display: none;"></div>
<div style="display: none;" class="wpProQuiz_quiz">
	<ol class="wpProQuiz_list">
					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:41,&quot;question_post_id&quot;:1369}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>1</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>1</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהם מרכיבי ה&quot;מרחב התעבורתי&quot;?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="41"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_41"
													value="1"> כל מה שנמצא בעולם, כולל הרכוש ומזג האוויר שבתוך הבתים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_41"
													value="2"> כל מה שנמצא בדרך וכל מי שמשתמש בדרך: פני הדרך, עוברי דרך, כלי הרכב ותנאי הסביבה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_41"
													value="3"> מרכיבים ששייכים לעולם התעבורה בלבד: עוברי דרך וכלי רכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_41"
													value="4"> מרכיבים ששייכים למרחב בלבד: פני הדרך ותנאי הסביבה.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:42,&quot;question_post_id&quot;:1370}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>2</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>2</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>במרחב התעבורתי מתקיימים מפגשים רבים בין עוברי דרך בהם עליהם להתחשב אחד בשני. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="42"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_42"
													value="1"> לא נכון. אם עוברי הדרך ינהגו לפי הוראות החוק לא יתקיימו נקודות מגע ביניהם.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_42"
													value="2"> לא נכון, בכל מקרה במרחב התעבורתי אין מפגשים בין עוברי דרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_42"
													value="3"> לא נכון, כל אחד ממשתמשי הדרך נע בנתיב שלו ולא נפגש עם אחרים.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_42"
													value="4"> נכון, מפגשים מתקיימים במרחב התעבורתי בכל שעות היום ובכל מקום בדרך.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. <span class="highlight-hint">ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</span></p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:43,&quot;question_post_id&quot;:1371}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>3</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>3</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>הנהיגה היא משימה מורכבת. נכון או לא נכון?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="43"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_43"
													value="1"> לא נכון. רק כשיש עומס בדרכים לפעמים קשה לנהוג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_43"
													value="2"> לא נכון. רק נהגי משאיות כבדות מתעייפים בנהיגה בגלל משאם הכבד.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_43"
													value="3"> נכון. מטלת הנהיגה דורשת ביצוע מספר מטלות במקביל ובזמן קצר וזה מורכב.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_43"
													value="4"> נכון. אבל רק בגלל שלנהגים אין סבלנות והם לא מעניקים זכות קדימה כנדרש בחוק.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב. עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p><span class="highlight-hint">הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</span></p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:44,&quot;question_post_id&quot;:1372}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>4</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>4</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם תלמיד נהיגה הוא חלק מהמרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="44"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_44"
													value="1"> לא. רק אחרי שיקבל רישיון נהיגה יהפוך תלמיד נהיגה לחלק מהמרחב התעבורתי.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_44"
													value="2"> לא. המרחב התעבורתי לא כולל תלמידי נהיגה הנוהגים ברכב ללימוד נהיגה.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_44"
													value="3"> כן, אבל רק אם הוא נוהג בדרך בינעירונית.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_44"
													value="4"> כן, כל מי שנמצא בדרך ומשתמש בה הוא חלק מהמרחב התעבורתי.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:45,&quot;question_post_id&quot;:1373}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>5</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>5</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>האם יש סיכונים במרחב התעבורתי?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="45"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_45"
													value="1"> ממש לא.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_45"
													value="2"> כן, רק בדרכים בינעירוניות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_45"
													value="3"> כן, המרחב התעבורתי מאופיין באי ודאות ובסכנות רבות.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_45"
													value="4"> כן, רק בדרכים עירוניות צפופות.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p><span class="highlight-hint">נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</span></p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:46,&quot;question_post_id&quot;:1374}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>6</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>6</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין עובר דרך לסביבה הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="46"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_46"
													value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_46"
													value="2"> מפגש בין תמרור להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_46"
													value="3"> מפגש בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_46"
													value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:47,&quot;question_post_id&quot;:1375}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>7</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>7</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מפגש בין הדרך לעובר דרך הוא:</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="47"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_47"
													value="1"> מפגש בצומת מרומזר בין הולך רגל לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_47"
													value="2"> מפגש בשעות הלילה בין נהג להולך רגל.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_47"
													value="3"> מפגש ביום גשום בין רוכב אופניים חשמליים לנהג.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_47"
													value="4"> נהיגה בשעה שקרני השמש מסנוורות את הנהג.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p>המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.<br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p><span class="highlight-hint">א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</span></p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

					<li class="wpProQuiz_listItem" style="display: none;" data-type="single" data-question-meta="{&quot;type&quot;:&quot;single&quot;,&quot;question_pro_id&quot;:48,&quot;question_post_id&quot;:1376}">
				<div class="wpProQuiz_question_page" style="display:none;" >
				שאלה <span>8</span> מתוך <span>8</span>				</div>
				<h5 style="display: none;" class="wpProQuiz_header">
					<span>8</span>. שאלה
				</h5>

				
								<div class="wpProQuiz_question" style="margin: 10px 0px 0px 0px;">
					<div class="wpProQuiz_question_text">
						<p>מהו &quot;מרחב תעבורתי&quot;?</p>
					</div>
					<p class="wpProQuiz_clear" style="clear:both;"></p>

										
										<ul class="wpProQuiz_questionList" data-question_id="48"
						data-type="single">
						
								<li class="wpProQuiz_questionListItem" data-pos="0">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_48"
													value="1"> מרחב תעבורתי הוא המרחב שבו כלי הרכב נעים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="1">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_48"
													value="2"> מרחב תעבורתי כולל את כל בני האדם המשתמשים בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="2">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_48"
													value="3"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך.										</label>

																		</li>
								
								<li class="wpProQuiz_questionListItem" data-pos="3">
																			<span style="display:none;"></span>
										<label>
											<input class="wpProQuiz_questionInput" autocomplete="off"
													type="radio"
													name="question_9_48"
													value="4"> מרחב תעבורתי הוא מושג רחב הכולל את כל מי שנמצא בדרך ואת כל מי שמשתמש בדרך, מלבד עגלות שאין להן מנוע.										</label>

																		</li>
													</ul>
									</div>
									<div class="wpProQuiz_response" style="display: none;">
						<div style="display: none;" class="wpProQuiz_correct">
															<span>
								נכון								</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
						<div style="display: none;" class="wpProQuiz_incorrect">
															<span>
								לא נכון							</span>
														<div class="wpProQuiz_AnswerMessage"></div>
						</div>
					</div>
				
									<div class="wpProQuiz_tipp" style="display: none; position: relative;">
						<div>
							<h5 style="margin: 0px 0px 10px;" class="wpProQuiz_header">
							רמז							</h5>
							<p><span class="highlight-hint">המרחב התעבורתי הוא מושג רחב הכולל בתוכו את כל מה שנמצא בדרך, את כל מי שמשתמש בדרך ואת כל מה שיש לו השפעה על הנסיעה בדרך.</span><br />
למרחב התעבורתי חברו ארבעה שותפים מרכזיים:</p>
<p>א. פני הדרך – כל מה שישנו בדרך ובסביבתה: מרחבים פתוחים, מרחבים בנויים, כבישים, מדרכות, תמרורים ורמזורים, ועוד. ב.עוברי הדרך – כל משתמשי הדרך הנמצאים בה לכל מטרה: להליכה, לרכיבה, לנסיעה ועוד. ג. כלי רכב – כל כלי הרכב הנעים בדרך על מאפייניהם וסוגיהם השונים. ד. הסביבה – כל תנאי שמשפיע על הנסיעה כגון: תנאי הנראות, שעות היום או הלילה או כמות הנוסעים בכביש.</p>
<p>כפי שתראו בתמונה, להקות בעלי חיים יכולות לנוע בצפיפות כגוף אחד מבלי להתנגש אחד בשני ובלי נפגעים.</p>
<p>שלושה מאפיינים עיקריים למרחב התעבורתי:</p>
<p>א. תנועה רבה ומהירה</p>
<p>ב. שונות ורבגוניות של משתמשי הדרך או של הרכבים הנעים בדרך, כגון השוני העצום שקיים בין רכב לבין אופנוע, או בין נהג ותיק ובעל ניסיון לנהג צעיר חדש חסר ניסיון.</p>
<p>ג. ריבוי מפגשים בין משתמשי הדרך – כל גורם הנע במרחב התעבורתי נתקל בדרכו במשתמשי דרך אחרים הנעים לצידו ומה שיקרה במפגש ביניהם תלוי באמצעי הזהירות שנקטו בהם.</p>
<p>נסכם ונאמר כי סכנות ואי וודאות רבה מאפיינות את המרחב התעבורתי בשל מאפייניו</p>
<p>מורכבותה של מטלת הנהיגה</p>
<p>הנהיגה היא מטלה מורכבת ומסובכת. תראו כמה פעולות על הנהג לבצע בזמן קצר: עליו לאסוף מידע על הסביבה. היות והדרך מתאפיינת בתנועה רבה ומהירה, בשונות גדולה בין משתתפי הדרך ובריבוי מפגשים ביניהם – פעולת איסוף המידע קשה במיוחד שהרי הסביבה משתנה או יכולה להשתנות בכל רגע.</p>
<p>משימה:</p>
<p>לפניכם סרטון הממחיש ומדגים את מורכבות מטלת הנהיגה ביום רגיל במנהטן. בתמונות דומות תתקלו גם בארצנו בערים הגדולות.</p>
<p>התוכלו להצביע על הרגעים בסרטון בהם הנהיגה מורכבת ומחייבת זהירות ועירנות רבה מצד הנהג? רשמו במהלך הצפייה לפחות 5 מצבים שדרשו מהנהג היערכות מיוחדת ותשומת לב מירבית.</p>
<p>הפנייה לסרטון מתוך “תורת החינוך התעבורתי” פרק 1 עמ` 11-12.</p>
<p>למורה: מומלץ מומלץ לעצור את הסרטון מפעם לפעם, לשתף את התלמידים ולהדגיש בפניהם את המורכבות של מצבי הנהיגה שראו בסרטון זה עתה.</p>
<p>מהירות התנועה היא נתון משפיע ביותר. פעמים רבות אירועים מתרחשים בקצב מהיר הרבה יותר ממה שנדרש לאדם באופן טבעי כדי להגיב להם. בנוסף, לכל נהג רצון משלו שמנוגד לעיתים לרצון חברו ופעמים רבות לנהגים אין רגישות מספקת לזולת ונהג צריך לקחת זאת בחשבון.</p>
<p>לכן נכון יהיה לחזור ולהזכיר שהמרחב התעבורתי מתאפיין באי ודאות ובסכנות רבות.</p>
<div class="btn-wrapper"><button id="mark-hint" type="button">סמן רמז</button><br />
<button id="close-hint" type="button">סגור רמז</button></div>
						</div>
					</div>
				
													<input type="button" name="next" value="הבא" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: left ; margin-right: 10px ; display: none;"> 													<input type="button" name="tip" value="רמז" class="wpProQuiz_button wpProQuiz_QuestionButton wpProQuiz_TipButton" style="float: left ; display: inline-block; margin-right: 10px ;"> 								<input type="button" name="check" value="סמן" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right ; margin-right: 10px ; display: none;"> 								<input type="button" name="back" value="חזרה" class="wpProQuiz_button wpProQuiz_QuestionButton" style="float: right; display: none;"> 								<div style="clear: both;"></div>

							</li>

			</ol>
	</div>
		</div>
		
</div> <!--/.learndash-wrapper-->
</div>
</div>
						</div>
				</div>
					</div>
				</div>
				</div>
		        </div>
    </div>
    
    <div class="lilac-login-forms-container">
        <div class="lilac-login-forms-row">
            <!-- Access Code Form (if enabled) -->
                        
            <!-- Unified Login Form -->
            <div class="lilac-login-form-col">
                <div class="lilac-login-form-wrapper">
                    <div class="lilac-login-container lilac-login-form">
    <h2>התחברות לאתר</h2>
    <p class="description">התחבר באמצעות מספר הטלפון/אימייל והסיסמה שלך</p>
    
        
    <form method="post" class="lilac-login-form">
        <div class="form-group">
            <label for="lilac-username">מספר טלפון / אימייל:</label>
            <input type="text" name="log" id="lilac-username" required 
                placeholder="הזן מספר טלפון או כתובת מייל" />
        </div>
        
        <div class="form-group">
            <label for="lilac-password">סיסמה:</label>
            <input type="password" name="pwd" id="lilac-password" required 
                placeholder="הזן סיסמה" />
        </div>
        
        <div class="form-group remember-me">
            <input type="checkbox" name="rememberme" id="lilac-rememberme" value="forever" />
            <label for="lilac-rememberme">זכור אותי</label>
        </div>
        
        <input type="hidden" name="lilac_login_action" value="1" />
        <input type="hidden" id="lilac_login_nonce" name="lilac_login_nonce" value="03bb79d790" /><input type="hidden" name="_wp_http_referer" value="/testlogin/" />        
        <div class="form-group submit-group">
            <button type="submit" class="lilac-login-button">
                התחבר            </button>
        </div>
        
        <div class="form-group links-group">
            <a href="https://lilac.local/my-account/lost-password/" class="forgot-password">
                שכחת סיסמה?            </a>
            
            <a href="https://lilac.local/testlogin/" class="register-link">הרשמה לאתר</a>        </div>
    </form>
</div>
<style>
    .lilac-login-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        direction: rtl;
        text-align: right;
    }

    .lilac-login-container h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #2c3e50;
        text-align: center;
    }

    .lilac-login-container .description {
        margin-bottom: 25px;
        color: #7f8c8d;
        text-align: center;
    }

    .lilac-login-form .form-group {
        margin-bottom: 20px;
    }

    .lilac-login-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .lilac-login-form input[type="text"],
    .lilac-login-form input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .lilac-login-form .remember-me {
        display: flex;
        align-items: center;
    }

    .lilac-login-form .remember-me input {
        margin-left: 8px;
    }

    .lilac-login-form .remember-me label {
        margin: 0;
        font-weight: normal;
    }

    .lilac-login-button {
        display: block;
        width: 100%;
        padding: 12px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .lilac-login-button:hover {
        background-color: #2980b9;
    }

    .links-group {
        display: flex;
        justify-content: space-between;
        margin-top: 15px;
    }

    .links-group a {
        color: #3498db;
        text-decoration: none;
    }

    .links-group a:hover {
        text-decoration: underline;
    }

    .login-error {
        background-color: #f8d7da;
        color: #721c24;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }

    .login-success {
        background-color: #d4edda;
        color: #155724;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        border: 1px solid #c3e6cb;
    }
    
    /* Two-column registration links */
    .registration-links {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .registration-links .reg-link {
        flex: 0 0 48%;
        text-align: center;
        background-color: #f8f9fa;
        padding: 15px 10px;
        border-radius: 4px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .registration-links .reg-link:hover {
        background-color: #e9ecef;
        transform: translateY(-2px);
    }
    
    .registration-links .reg-link a {
        text-decoration: none;
        color: #2c3e50;
        font-weight: 600;
        display: block;
    }
    
    .registration-links .reg-link span {
        display: block;
        font-size: 0.9em;
        color: #6c757d;
        margin-top: 5px;
    }
    
    @media (max-width: 576px) {
        .registration-links .reg-link {
            flex: 0 0 100%;
        }
    }
</style>
                </div>
            </div>
            
            <!-- Registration Links -->
            <div class="lilac-login-form-col">
                <div class="lilac-registration-links-wrapper">
                    <h2>לא רשום עדיין?</h2>
                    <p class="description">בחר את סוג החשבון המתאים לך:</p>
                    
                    <div class="registration-links">
                                                <div class="reg-link school">
                            <a href="https://lilac.local/testlogin/">
                                הרשמה לחינוך התעבורתי
                                <span>לתלמידי כיתה י'</span>
                            </a>
                        </div>
                        <div class="reg-link private">
                            <a href="https://lilac.local/testlogin/">
                                הרשמה ללקוחות פרטיים
                                <span>לכל המעוניינים</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .lilac-test-login-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    .lilac-page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .lilac-page-header h1 {
        margin-bottom: 20px;
    }
    
    .lilac-page-content {
        margin-bottom: 30px;
    }
    
    .lilac-login-forms-container {
        display: flex;
        justify-content: center;
        direction: rtl;
    }
    
    .lilac-login-forms-row {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 30px;
        width: 100%;
    }
    
    .lilac-login-form-col {
        flex: 0 0 calc(50% - 15px);
        max-width: calc(50% - 15px);
    }
    
    .lilac-access-code-form {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .lilac-divider {
        width: 100%;
        border: 0;
        border-top: 1px solid #e9ecef;
        margin: 30px 0;
    }
    
    .lilac-registration-links-wrapper {
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 8px;
        text-align: center;
    }
    
    .lilac-registration-links-wrapper h2 {
        margin-top: 0;
        margin-bottom: 15px;
    }
    
    .registration-links {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 20px;
    }
    
    .registration-links .reg-link {
        padding: 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .registration-links .reg-link.school {
        background-color: #e3f2fd;
    }
    
    .registration-links .reg-link.private {
        background-color: #e8f5e9;
    }
    
    .registration-links .reg-link:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .registration-links .reg-link a {
        display: block;
        text-decoration: none;
        color: #2c3e50;
        font-weight: 600;
        font-size: 16px;
    }
    
    .registration-links .reg-link span {
        display: block;
        color: #7f8c8d;
        font-size: 14px;
        font-weight: normal;
        margin-top: 5px;
    }
    
    @media (max-width: 768px) {
        .lilac-login-forms-row {
            flex-direction: column;
        }
        
        .lilac-login-form-col {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 30px;
        }
    }
</style>

		<div data-elementor-type="footer" data-elementor-id="1287" class="elementor elementor-1287 elementor-location-footer" data-elementor-post-type="elementor_library">
			<div class="elementor-element elementor-element-354aa413 e-flex e-con-boxed e-con e-parent" data-id="354aa413" data-element_type="container" data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
					<div class="e-con-inner">
				<div class="elementor-element elementor-element-2e4d3e28 elementor-widget elementor-widget-heading" data-id="2e4d3e28" data-element_type="widget" data-widget_type="heading.default">
				<div class="elementor-widget-container">
					<p class="elementor-heading-title elementor-size-default">© All Rights Reserved.</p>				</div>
				</div>
					</div>
				</div>
		<div class="elementor-element elementor-element-7814dff e-flex e-con-boxed e-con e-parent" data-id="7814dff" data-element_type="container">
					<div class="e-con-inner">
				<div class="elementor-element elementor-element-07db37c elementor-widget elementor-widget-shortcode" data-id="07db37c" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">
</div>
						</div>
				</div>
					</div>
				</div>
				</div>
		
<script type="speculationrules">
{"prefetch":[{"source":"document","where":{"and":[{"href_matches":"\/*"},{"not":{"href_matches":["\/wp-*.php","\/wp-admin\/*","\/wp-content\/uploads\/*","\/wp-content\/*","\/wp-content\/plugins\/*","\/wp-content\/themes\/hello-theme-child-master\/*","\/wp-content\/themes\/hello-elementor\/*","\/*\\?(.+)"]}},{"not":{"selector_matches":"a[rel~=\"nofollow\"]"}},{"not":{"selector_matches":".no-prefetch, .no-prefetch a"}}]},"eagerness":"conservative"}]}
</script>
			<script>
				const lazyloadRunObserver = () => {
					const lazyloadBackgrounds = document.querySelectorAll( `.e-con.e-parent:not(.e-lazyloaded)` );
					const lazyloadBackgroundObserver = new IntersectionObserver( ( entries ) => {
						entries.forEach( ( entry ) => {
							if ( entry.isIntersecting ) {
								let lazyloadBackground = entry.target;
								if( lazyloadBackground ) {
									lazyloadBackground.classList.add( 'e-lazyloaded' );
								}
								lazyloadBackgroundObserver.unobserve( entry.target );
							}
						});
					}, { rootMargin: '200px 0px 200px 0px' } );
					lazyloadBackgrounds.forEach( ( lazyloadBackground ) => {
						lazyloadBackgroundObserver.observe( lazyloadBackground );
					} );
				};
				const events = [
					'DOMContentLoaded',
					'elementor/lazyload/observe',
				];
				events.forEach( ( event ) => {
					document.addEventListener( event, lazyloadRunObserver );
				} );
			</script>
				<script>
		(function () {
			var c = document.body.className;
			c = c.replace(/woocommerce-no-js/, 'woocommerce-js');
			document.body.className = c;
		})();
	</script>
	<link rel='stylesheet' id='wc-blocks-style-rtl-css' href='https://lilac.local/wp-content/plugins/woocommerce/assets/client/blocks/wc-blocks-rtl.css?ver=1745821976' media='all' />
<style id='core-block-supports-inline-css'>
/**
 * Core styles: block-supports
 */

</style>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_pager.js?ver=4.20.2.1-1746019143" id="learndash_pager_js-js"></script>
<script id="learndash_template_script_js-js-extra">
var sfwd_data = {"json":"{\"ajaxurl\":\"https:\\\/\\\/lilac.local\\\/wp-admin\\\/admin-ajax.php\"}"};
</script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/legacy/templates/learndash_template_script.js?ver=4.20.2.1-1746019143" id="learndash_template_script_js-js"></script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/assets/js/jquery.dropdown.min.js?ver=4.20.2.1-1746019143" id="jquery-dropdown-js-js"></script>
<script id="ld-hints-script-js-extra">
var LD_HINTS_AJAX = {"ajax_url":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"cb096f20b7"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/learndash-hints/js/learndash-hints.js" id="ld-hints-script-js"></script>
<script src="https://lilac.local/wp-content/themes/hello-elementor/assets/js/hello-frontend.js?ver=3.3.0" id="hello-theme-frontend-js"></script>
<script id="lilac-toast-js-extra">
var lilacToastData = {"sessionCounter":"87","ajaxUrl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"961905f0db"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/js/toast.js?ver=1745390895" id="lilac-toast-js"></script>
<script id="lilac-progress-js-extra">
var lilacProgressData = {"ajaxUrl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","courseViews":{"1292":{"total":4,"items":{"1292":4}},"898":{"total":3,"items":{"898":3}}},"courseProgress":[],"lastActivity":"1746015942","nonce":"919ddf2d1c"};
</script>
<script src="https://lilac.local/wp-content/themes/hello-theme-child-master/src/Messaging/assets/js/progress.js?ver=1745374079" id="lilac-progress-js"></script>
<script id="learndash-front-js-extra">
var ldVars = {"postID":"1219","videoReqMsg":"\u05d0\u05ea\u05d4 \u05d7\u05d9\u05d9\u05d1 \u05dc\u05e6\u05e4\u05d5\u05ea \u05d1\u05e1\u05e8\u05d8\u05d5\u05df \u05db\u05d3\u05d9 \u05dc\u05d2\u05e9\u05ea \u05dc\u05ea\u05d5\u05db\u05df \u05d4\u05d6\u05d4","ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php"};
</script>
<script src="//lilac.local/wp-content/plugins/sfwd-lms/themes/ld30/assets/js/learndash.js?ver=4.20.2.1-1746019143" id="learndash-front-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/lib/smartmenus/jquery.smartmenus.js?ver=1.2.1" id="smartmenus-js"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/sourcebuster/sourcebuster.js?ver=9.8.2" id="sourcebuster-js-js"></script>
<script id="wc-order-attribution-js-extra">
var wc_order_attribution = {"params":{"lifetime":1.0000000000000001e-5,"session":30,"base64":false,"ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","prefix":"wc_order_attribution_","allowTracking":true},"fields":{"source_type":"current.typ","referrer":"current_add.rf","utm_campaign":"current.cmp","utm_source":"current.src","utm_medium":"current.mdm","utm_content":"current.cnt","utm_id":"current.id","utm_term":"current.trm","utm_source_platform":"current.plt","utm_creative_format":"current.fmt","utm_marketing_tactic":"current.tct","session_entry":"current_add.ep","session_start_time":"current_add.fd","session_pages":"session.pgs","session_count":"udata.vst","user_agent":"udata.uag"}};
</script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/frontend/order-attribution.js?ver=9.8.2" id="wc-order-attribution-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/core.js?ver=1.13.3" id="jquery-ui-core-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/mouse.js?ver=1.13.3" id="jquery-ui-mouse-js"></script>
<script src="https://lilac.local/wp-includes/js/jquery/ui/sortable.js?ver=1.13.3" id="jquery-ui-sortable-js"></script>
<script id="wpProQuiz_front_javascript-js-extra">
var WpProQuizGlobal = {"ajaxurl":"\/\/lilac.local\/wp-admin\/admin-ajax.php","loadData":"\u05d8\u05d5\u05e2\u05df","questionNotSolved":"\u05e2\u05dc\u05d9\u05da \u05dc\u05e2\u05e0\u05d5\u05ea \u05e2\u05dc \u05e9\u05d0\u05dc\u05d4 \u05d6\u05d5.","questionsNotSolved":"You must answer all \u05e9\u05d0\u05dc\u05d5\u05ea before you can complete the \u05de\u05d1\u05d7\u05df.","fieldsNotFilled":"\u05d7\u05d5\u05d1\u05d4 \u05dc\u05de\u05dc\u05d0 \u05d0\u05ea \u05db\u05dc \u05d4\u05e9\u05d3\u05d5\u05ea."};
var WpProQuizGlobal = {"ajaxurl":"\/\/lilac.local\/wp-admin\/admin-ajax.php","loadData":"\u05d8\u05d5\u05e2\u05df","questionNotSolved":"\u05e2\u05dc\u05d9\u05da \u05dc\u05e2\u05e0\u05d5\u05ea \u05e2\u05dc \u05e9\u05d0\u05dc\u05d4 \u05d6\u05d5.","questionsNotSolved":"You must answer all \u05e9\u05d0\u05dc\u05d5\u05ea before you can complete the \u05de\u05d1\u05d7\u05df.","fieldsNotFilled":"\u05d7\u05d5\u05d1\u05d4 \u05dc\u05de\u05dc\u05d0 \u05d0\u05ea \u05db\u05dc \u05d4\u05e9\u05d3\u05d5\u05ea."};
</script>
<script src="https://lilac.local/wp-content/plugins/sfwd-lms/includes/lib/wp-pro-quiz/js/wpProQuiz_front.js?ver=4.20.2.1-1746019143" id="wpProQuiz_front_javascript-js"></script>
<script src="https://lilac.local/wp-content/plugins/woocommerce/assets/js/jquery-cookie/jquery.cookie.js?ver=1.4.1-wc.9.8.2" id="jquery-cookie-js" defer data-wp-strategy="defer"></script>
<script src="https://lilac.local/wp-content/plugins/sfwd-lms/includes/lib/wp-pro-quiz/js/jquery.ui.touch-punch.min.js?ver=0.2.2" id="jquery-ui-touch-punch-js"></script>
<script src="https://lilac.local/wp-content/plugins/sfwd-lms/includes/lib/wp-pro-quiz/js/wpProQuiz_toplist.js?ver=4.20.2.1-1746019143" id="wpProQuiz_front_javascript_toplist-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/webpack-pro.runtime.js?ver=3.28.3" id="elementor-pro-webpack-runtime-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/webpack.runtime.js?ver=3.28.4" id="elementor-webpack-runtime-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/frontend-modules.js?ver=3.28.4" id="elementor-frontend-modules-js"></script>
<script src="https://lilac.local/wp-includes/js/dist/hooks.js?ver=be67dc331e61e06d52fa" id="wp-hooks-js"></script>
<script src="https://lilac.local/wp-includes/js/dist/i18n.js?ver=5edc734adb78e0d7d00e" id="wp-i18n-js"></script>
<script id="wp-i18n-js-after">
wp.i18n.setLocaleData( { 'text direction\u0004ltr': [ 'rtl' ] } );
</script>
<script id="elementor-pro-frontend-js-before">
var ElementorProFrontendConfig = {"ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","nonce":"bdc9dff58e","urls":{"assets":"https:\/\/lilac.local\/wp-content\/plugins\/elementor-pro\/assets\/","rest":"https:\/\/lilac.local\/wp-json\/"},"settings":{"lazy_load_background_images":true},"popup":{"hasPopUps":true},"shareButtonsNetworks":{"facebook":{"title":"Facebook","has_counter":true},"twitter":{"title":"Twitter"},"linkedin":{"title":"LinkedIn","has_counter":true},"pinterest":{"title":"Pinterest","has_counter":true},"reddit":{"title":"Reddit","has_counter":true},"vk":{"title":"VK","has_counter":true},"odnoklassniki":{"title":"OK","has_counter":true},"tumblr":{"title":"Tumblr"},"digg":{"title":"Digg"},"skype":{"title":"Skype"},"stumbleupon":{"title":"StumbleUpon","has_counter":true},"mix":{"title":"Mix"},"telegram":{"title":"Telegram"},"pocket":{"title":"Pocket","has_counter":true},"xing":{"title":"XING","has_counter":true},"whatsapp":{"title":"WhatsApp"},"email":{"title":"Email"},"print":{"title":"Print"},"x-twitter":{"title":"X"},"threads":{"title":"Threads"}},"woocommerce":{"menu_cart":{"cart_page_url":"https:\/\/lilac.local\/cart\/","checkout_page_url":"https:\/\/lilac.local\/checkout\/","fragments_nonce":"0b86dd1450"}},"facebook_sdk":{"lang":"he_IL","app_id":""},"lottie":{"defaultAnimationUrl":"https:\/\/lilac.local\/wp-content\/plugins\/elementor-pro\/modules\/lottie\/assets\/animations\/default.json"}};
</script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/frontend.js?ver=3.28.3" id="elementor-pro-frontend-js"></script>
<script id="elementor-frontend-js-before">
var elementorFrontendConfig = {"environmentMode":{"edit":false,"wpPreview":false,"isScriptDebug":true},"i18n":{"shareOnFacebook":"\u05e9\u05ea\u05e3 \u05d1\u05e4\u05d9\u05d9\u05e1\u05d1\u05d5\u05e7","shareOnTwitter":"\u05e9\u05ea\u05e3 \u05d1\u05d8\u05d5\u05d5\u05d9\u05d8\u05e8","pinIt":"\u05dc\u05e0\u05e2\u05d5\u05e5 \u05d1\u05e4\u05d9\u05e0\u05d8\u05e8\u05e1\u05d8","download":"\u05d4\u05d5\u05e8\u05d3\u05d4","downloadImage":"\u05d4\u05d5\u05e8\u05d3\u05ea \u05ea\u05de\u05d5\u05e0\u05d4","fullscreen":"\u05de\u05e1\u05da \u05de\u05dc\u05d0","zoom":"\u05de\u05d9\u05e7\u05d5\u05d3","share":"\u05e9\u05ea\u05e3","playVideo":"\u05e0\u05d2\u05df \u05d5\u05d9\u05d3\u05d0\u05d5","previous":"\u05e7\u05d5\u05d3\u05dd","next":"\u05d4\u05d1\u05d0","close":"\u05e1\u05d2\u05d5\u05e8","a11yCarouselPrevSlideMessage":"\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05e7\u05d5\u05d3\u05de\u05ea","a11yCarouselNextSlideMessage":"\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05d1\u05d0\u05d4","a11yCarouselFirstSlideMessage":"\u05d6\u05d5\u05d4\u05d9 \u05d4\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05e8\u05d0\u05e9\u05d5\u05e0\u05d4","a11yCarouselLastSlideMessage":"\u05d6\u05d5\u05d4\u05d9 \u05d4\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea \u05d4\u05d0\u05d7\u05e8\u05d5\u05e0\u05d4","a11yCarouselPaginationBulletMessage":"\u05dc\u05e2\u05d1\u05d5\u05e8 \u05dc\u05e9\u05e7\u05d5\u05e4\u05d9\u05ea"},"is_rtl":true,"breakpoints":{"xs":0,"sm":480,"md":768,"lg":1025,"xl":1440,"xxl":1600},"responsive":{"breakpoints":{"mobile":{"label":"\u05de\u05d5\u05d1\u05d9\u05d9\u05dc \u05d0\u05e0\u05db\u05d9","value":767,"default_value":767,"direction":"max","is_enabled":true},"mobile_extra":{"label":"\u05de\u05d5\u05d1\u05d9\u05d9\u05dc \u05d0\u05d5\u05e4\u05e7\u05d9","value":880,"default_value":880,"direction":"max","is_enabled":false},"tablet":{"label":"\u05d8\u05d0\u05d1\u05dc\u05d8 \u05d0\u05e0\u05db\u05d9","value":1024,"default_value":1024,"direction":"max","is_enabled":true},"tablet_extra":{"label":"\u05d8\u05d0\u05d1\u05dc\u05d8 \u05d0\u05d5\u05e4\u05e7\u05d9","value":1200,"default_value":1200,"direction":"max","is_enabled":false},"laptop":{"label":"\u05dc\u05e4\u05d8\u05d5\u05e4","value":1366,"default_value":1366,"direction":"max","is_enabled":false},"widescreen":{"label":"\u05de\u05e1\u05da \u05e8\u05d7\u05d1","value":2400,"default_value":2400,"direction":"min","is_enabled":false}},"hasCustomBreakpoints":false},"version":"3.28.4","is_static":false,"experimentalFeatures":{"e_font_icon_svg":true,"additional_custom_breakpoints":true,"container":true,"e_local_google_fonts":true,"theme_builder_v2":true,"hello-theme-header-footer":true,"nested-elements":true,"editor_v2":true,"e_element_cache":true,"home_screen":true,"launchpad-checklist":true},"urls":{"assets":"https:\/\/lilac.local\/wp-content\/plugins\/elementor\/assets\/","ajaxurl":"https:\/\/lilac.local\/wp-admin\/admin-ajax.php","uploadUrl":"https:\/\/lilac.local\/wp-content\/uploads"},"nonces":{"floatingButtonsClickTracking":"1039d9c060"},"swiperClass":"swiper","settings":{"page":[],"editorPreferences":[]},"kit":{"active_breakpoints":["viewport_mobile","viewport_tablet"],"global_image_lightbox":"yes","lightbox_enable_counter":"yes","lightbox_enable_fullscreen":"yes","lightbox_enable_zoom":"yes","lightbox_enable_share":"yes","lightbox_title_src":"title","lightbox_description_src":"description","woocommerce_notices_elements":[],"hello_header_logo_type":"title","hello_header_menu_layout":"horizontal","hello_footer_logo_type":"logo"},"post":{"id":1219,"title":"%D7%91%D7%93%D7%99%D7%A7%D7%AA%20%D7%98%D7%A4%D7%A1%D7%99%20%D7%94%D7%A8%D7%A9%D7%9E%D7%94%20%E2%80%93%20lilac","excerpt":"","featuredImage":false}};
</script>
<script src="https://lilac.local/wp-content/plugins/elementor/assets/js/frontend.js?ver=3.28.4" id="elementor-frontend-js"></script>
<script src="https://lilac.local/wp-content/plugins/elementor-pro/assets/js/elements-handlers.js?ver=3.28.3" id="pro-elements-handlers-js"></script>
 <script type='text/javascript'>
		function load_wpProQuizFront7() {
			jQuery('#wpProQuiz_7').wpProQuizFront({
				course_id: 0,
				lesson_id: 0,
				topic_id: 0,
				quiz: 1309,
				quizId: 7,
				mode: 2,
				globalPoints: 0,
				timelimit: 0,
				timelimitcookie: 0,
				resultsGrade: [0],
				bo: 7552,
				passingpercentage: 80,
				user_id: 0,
				qpp: 0,
				catPoints: [0],
				formPos: 0,
				essayUploading: 'מעלה',
				essaySuccess: 'הצלחה',
				lbn: "\u05e1\u05d9\u05d9\u05dd \u05de\u05d1\u05d7\u05df",
				json: {"30":{"type":"single","id":30,"question_post_id":1310,"catId":0},"31":{"type":"single","id":31,"question_post_id":1311,"catId":0},"32":{"type":"single","id":32,"question_post_id":1312,"catId":0},"33":{"type":"single","id":33,"question_post_id":1313,"catId":0},"34":{"type":"single","id":34,"question_post_id":1314,"catId":0},"35":{"type":"single","id":35,"question_post_id":1315,"catId":0},"36":{"type":"single","id":36,"question_post_id":1316,"catId":0},"37":{"type":"single","id":37,"question_post_id":1317,"catId":0},"38":{"type":"single","id":38,"question_post_id":1318,"catId":0}},
				ld_script_debug: 0,
				quiz_nonce: '11d36399ab',
				scrollSensitivity: '10',
				scrollSpeed: '10',
				quiz_resume_enabled:  '0',
				quiz_resume_id: '0',
				quiz_resume_quiz_started: '0',
				quiz_resume_data: '[]',
				quiz_resume_cookie_expiration: '604800',
				quiz_resume_cookie_send_timer: '5',
			});
		}
		var loaded_wpProQuizFront7 = 0;
		jQuery( function($) {
			load_wpProQuizFront7();
			loaded_wpProQuizFront7 = 1;
		});
		jQuery(window).on('load',function($) {
			if(loaded_wpProQuizFront7 == 0)
			load_wpProQuizFront7();
		});
		</script>  <script type='text/javascript'>
		function load_wpProQuizFront9() {
			jQuery('#wpProQuiz_9').wpProQuizFront({
				course_id: 0,
				lesson_id: 0,
				topic_id: 0,
				quiz: 1367,
				quizId: 9,
				mode: 2,
				globalPoints: 0,
				timelimit: 0,
				timelimitcookie: 0,
				resultsGrade: [0],
				bo: 7552,
				passingpercentage: 80,
				user_id: 0,
				qpp: 0,
				catPoints: [0],
				formPos: 0,
				essayUploading: 'מעלה',
				essaySuccess: 'הצלחה',
				lbn: "\u05e1\u05d9\u05d9\u05dd \u05de\u05d1\u05d7\u05df",
				json: {"41":{"type":"single","id":41,"question_post_id":1369,"catId":0},"42":{"type":"single","id":42,"question_post_id":1370,"catId":0},"43":{"type":"single","id":43,"question_post_id":1371,"catId":0},"44":{"type":"single","id":44,"question_post_id":1372,"catId":0},"45":{"type":"single","id":45,"question_post_id":1373,"catId":0},"46":{"type":"single","id":46,"question_post_id":1374,"catId":0},"47":{"type":"single","id":47,"question_post_id":1375,"catId":0},"48":{"type":"single","id":48,"question_post_id":1376,"catId":0}},
				ld_script_debug: 0,
				quiz_nonce: '027d382ef8',
				scrollSensitivity: '10',
				scrollSpeed: '10',
				quiz_resume_enabled:  '0',
				quiz_resume_id: '0',
				quiz_resume_quiz_started: '0',
				quiz_resume_data: '[]',
				quiz_resume_cookie_expiration: '604800',
				quiz_resume_cookie_send_timer: '5',
			});
		}
		var loaded_wpProQuizFront9 = 0;
		jQuery( function($) {
			load_wpProQuizFront9();
			loaded_wpProQuizFront9 = 1;
		});
		jQuery(window).on('load',function($) {
			if(loaded_wpProQuizFront9 == 0)
			load_wpProQuizFront9();
		});
		</script> 
</body>
</html>
