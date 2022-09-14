/**
 * Table of content JS.
 *
 * Stops the sticky table of contents on wide screens from overlapping the footer
 * by positioning it slightly above
 */

document.body.onload = function () {
	const toc = document.querySelector( '.table-of-contents' );

	if ( ! toc ) {
		return;
	}

	const resetToCPosition = () => {
		if ( toc.style ) {
			toc.removeAttribute( 'style' );
		}
	};

	const tocBottom = toc.getBoundingClientRect().bottom;
	const footer = document.querySelector( '.global-footer' );
	const windowHeight = window.innerHeight;

	const setToCPosition = () => {
		if ( window.getComputedStyle( toc ).position !== 'fixed' ) {
			resetToCPosition();
			return;
		}

		const footerTop = footer.getBoundingClientRect().top;
		const exposedFooter = windowHeight - footerTop + 30;
		// if the bottom of the toc is less than the exposed footer move it to that position
		if ( windowHeight - tocBottom < exposedFooter ) {
			toc.style.bottom = exposedFooter + 'px';
			toc.style.top = 'initial';
		} else {
			resetToCPosition();
		}
	};

	setToCPosition();

	window.addEventListener( 'scroll', setToCPosition );
	window.addEventListener( 'resize', setToCPosition );
};
