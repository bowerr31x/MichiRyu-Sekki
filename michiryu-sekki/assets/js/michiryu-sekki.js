( function () {
	var progressKey = 'michiryuSekkiReadStories';
	var lastReadKey = 'michiryuSekkiLastReadStory';
	var totalStories = 72;

	function focusCurrentSlide( track ) {
		var currentSlide = track.querySelector( '.michiryu-sekki-carousel__slide.is-current' );

		if ( ! currentSlide ) {
			return;
		}

		var targetLeft = currentSlide.offsetLeft - ( ( track.clientWidth - currentSlide.clientWidth ) / 2 );

		track.scrollTo( {
			left: Math.max( 0, targetLeft ),
			behavior: 'auto',
		} );
	}

	function getReadStories() {
		var stored;
		var parsed;

		try {
			stored = window.localStorage ? window.localStorage.getItem( progressKey ) : '';
			parsed = stored ? JSON.parse( stored ) : [];
		} catch ( error ) {
			return [];
		}

		return Array.isArray( parsed ) ? parsed.filter( Boolean ) : [];
	}

	function saveReadStories( stories ) {
		try {
			if ( window.localStorage ) {
				window.localStorage.setItem( progressKey, JSON.stringify( stories ) );
			}
		} catch ( error ) {
			return;
		}
	}

	function getLastReadStory() {
		try {
			return window.localStorage ? window.localStorage.getItem( lastReadKey ) || '' : '';
		} catch ( error ) {
			return '';
		}
	}

	function saveLastReadStory( storyId ) {
		try {
			if ( window.localStorage && storyId ) {
				window.localStorage.setItem( lastReadKey, storyId );
			}
		} catch ( error ) {
			return;
		}
	}

	function updateProgressDisplay( container, readStories ) {
		var countTarget = container.querySelector( '[data-mrs-read-count]' );
		var bar = container.querySelector( '[data-mrs-read-bar]' );
		var currentStory = container.getAttribute( 'data-story' ) || container.getAttribute( 'data-current-story' ) || '';
		var count = readStories.length;
		var percent = Math.min( 100, Math.max( 0, ( count / totalStories ) * 100 ) );

		if ( countTarget ) {
			countTarget.textContent = count + ' of ' + totalStories + ' stories read';
		}

		if ( bar ) {
			bar.style.width = percent.toFixed( 2 ) + '%';
		}

		container.querySelectorAll( '[data-mrs-story-progress-item]' ).forEach( function ( item ) {
			var storyId = item.getAttribute( 'data-story' ) || '';
			var koNumber = item.getAttribute( 'data-ko' ) || '';
			var status = readStories.indexOf( storyId ) === -1 ? 'unread' : 'read';
			var label = item.getAttribute( 'data-status-label' ) || ( 'Ko ' + koNumber );

			item.classList.toggle( 'is-read', 'read' === status );
			item.classList.toggle( 'is-current', storyId && storyId === currentStory );
			item.setAttribute( 'aria-label', label + ', ' + status );

			if ( storyId && storyId === currentStory ) {
				item.setAttribute( 'aria-current', 'step' );
			} else {
				item.removeAttribute( 'aria-current' );
			}
		} );
	}

	function updateAllProgressDisplays( readStories ) {
		document.querySelectorAll( '[data-mrs-story-reader], [data-mrs-journey-progress]' ).forEach( function ( container ) {
			updateProgressDisplay( container, readStories );
		} );
	}

	function getStoryIndex() {
		var index = document.querySelector( '[data-mrs-story-index]' );

		if ( ! index ) {
			return [];
		}

		return Array.prototype.slice.call( index.querySelectorAll( 'a[data-story]' ) ).map( function ( link ) {
			return {
				id: link.getAttribute( 'data-story' ),
				url: link.getAttribute( 'href' ),
			};
		} ).filter( function ( story ) {
			return story.id && story.url;
		} );
	}

	function getNextUnreadStory( readStories ) {
		var index = getStoryIndex();
		var nextUnread = index.find( function ( story ) {
			return readStories.indexOf( story.id ) === -1;
		} );

		return nextUnread || index[0] || null;
	}

	function getNextStoryAfterLastRead( readStories ) {
		var index = getStoryIndex();
		var lastRead = getLastReadStory();
		var lastIndex;

		if ( ! index.length || ! lastRead ) {
			return getNextUnreadStory( readStories );
		}

		lastIndex = index.findIndex( function ( story ) {
			return story.id === lastRead;
		} );

		if ( lastIndex < 0 ) {
			return getNextUnreadStory( readStories );
		}

		return index[ ( lastIndex + 1 ) % index.length ];
	}

	function updateContinueJourneyLinks( readStories ) {
		var nextUnread = getNextStoryAfterLastRead( readStories );

		if ( ! nextUnread ) {
			return;
		}

		document.querySelectorAll( '[data-mrs-continue-journey]' ).forEach( function ( link ) {
			link.setAttribute( 'href', nextUnread.url );
		} );
	}

	function initializeStoryReaderProgress() {
		var readStories = getReadStories();

		document.querySelectorAll( '[data-mrs-story-reader]' ).forEach( function ( reader ) {
			var storyId = reader.getAttribute( 'data-story' );

			if ( storyId && readStories.indexOf( storyId ) === -1 ) {
				readStories.push( storyId );
				saveReadStories( readStories );
			}

			if ( storyId ) {
				saveLastReadStory( storyId );
			}
		} );

		updateAllProgressDisplays( readStories );
		updateContinueJourneyLinks( readStories );
	}

	function restartJourney( button ) {
		var reader = button ? button.closest( '[data-mrs-story-reader]' ) : null;
		var message = 'Restart your journey and clear reading progress in this browser?';

		if ( ! window.confirm( message ) ) {
			return;
		}

		saveReadStories( [] );
		try {
			if ( window.localStorage ) {
				window.localStorage.removeItem( lastReadKey );
			}
		} catch ( error ) {
			return;
		}
		updateAllProgressDisplays( [] );
		updateContinueJourneyLinks( [] );
	}

	function focusCurrentSlides() {
		var tracks = document.querySelectorAll( '.michiryu-sekki-carousel__track' );

		tracks.forEach( focusCurrentSlide );
	}

	function moveTrack( button, direction ) {
		var trackId = button.getAttribute( 'aria-controls' );
		var track = trackId ? document.getElementById( trackId ) : null;

		if ( ! track ) {
			return;
		}

		var firstSlide = track.querySelector( '.michiryu-sekki-carousel__slide' );
		var distance = firstSlide ? firstSlide.getBoundingClientRect().width : track.clientWidth;
		var gap = parseFloat( window.getComputedStyle( track ).columnGap || 0 );

		track.scrollBy( {
			left: direction * ( distance + gap ),
			behavior: 'smooth',
		} );
	}

	function getMapRoot( element ) {
		return element ? element.closest( '[data-mrs-map]' ) : null;
	}

	function getFocusableElements( container ) {
		if ( ! container ) {
			return [];
		}

		return Array.prototype.slice.call(
			container.querySelectorAll( 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])' )
		).filter( function ( element ) {
			return ! element.closest( '[hidden]' ) && element.offsetParent !== null;
		} );
	}

	function getOpenModal() {
		return document.querySelector( '[data-mrs-map-modal][data-open="true"], [data-mrs-learn-modal][data-open="true"], [data-mrs-story-modal][data-open="true"]' );
	}

	function trapModalFocus( event, modal ) {
		var focusable = getFocusableElements( modal );
		var first = focusable[0];
		var last = focusable[ focusable.length - 1 ];

		if ( ! focusable.length ) {
			return;
		}

		if ( ! modal.contains( document.activeElement ) ) {
			event.preventDefault();
			first.focus();
			return;
		}

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function updateProgressionCompass( map, slug ) {
		var compass = map ? map.querySelector( '[data-mrs-progression="wheel"]' ) : null;
		var markers = compass ? Array.prototype.slice.call( compass.querySelectorAll( '[data-mrs-progression-marker]' ) ) : [];
		var activeIndex;
		var seasonCount;

		if ( ! compass || ! markers.length ) {
			return;
		}

		activeIndex = markers.findIndex( function ( marker ) {
			return marker.getAttribute( 'data-season' ) === slug;
		} );

		if ( activeIndex < 0 ) {
			return;
		}

		seasonCount = markers.length;
		markers.forEach( function ( marker, index ) {
			var angle = ( ( index - activeIndex ) * ( 360 / seasonCount ) ) - 38;
			marker.style.setProperty( '--mrs-progress-angle', angle.toFixed( 3 ) + 'deg' );
		} );

		compass.querySelectorAll( '[data-mrs-progression-summary]' ).forEach( function ( summary ) {
			var isActive = summary.getAttribute( 'data-season' ) === slug;
			summary.classList.toggle( 'is-active', isActive );
			summary.hidden = ! isActive;
		} );
	}

	function centerTimelineMarker( marker ) {
		var timeline = marker ? marker.closest( '.michiryu-sekki-map__timeline' ) : null;
		var season;
		var primaryMarker;
		var targetLeft;

		if ( ! timeline ) {
			return;
		}

		season = marker.getAttribute( 'data-season' );
		primaryMarker = timeline.querySelector( '[data-mrs-progression-marker][data-season="' + season + '"][data-mrs-timeline-set="current"]' );
		marker = primaryMarker || marker;

		targetLeft = marker.offsetLeft - ( ( timeline.clientWidth - marker.offsetWidth ) / 2 );
		timeline.scrollTo( {
			left: Math.max( 0, targetLeft ),
			behavior: 'smooth',
		} );
	}

	function centerActiveTimelines() {
		document.querySelectorAll( '[data-mrs-timeline]' ).forEach( function ( timeline ) {
			var marker = timeline.querySelector( '[data-mrs-progression-marker].is-active[data-mrs-timeline-set="current"]' );
			if ( marker ) {
				centerTimelineMarker( marker );
			}
		} );
	}

	function selectMapSeason( map, slug, focusMarker ) {
		var markers = map.querySelectorAll( '[data-mrs-map-marker]' );
		var progressionMarkers = map.querySelectorAll( '[data-mrs-progression-marker]' );
		var paths = map.querySelectorAll( '[data-mrs-map-path]' );
		var details = map.querySelectorAll( '.michiryu-sekki-map__detail' );
		var stories = map.querySelectorAll( '.michiryu-sekki-map__stories' );
		var popovers = map.querySelectorAll( '[data-mrs-character-popover]' );
		var activeMarker = null;
		var activeStorySection = null;
		var storyTab = null;

		markers.forEach( function ( marker ) {
			var isActive = marker.getAttribute( 'data-season' ) === slug;
			marker.classList.toggle( 'is-active', isActive );
			marker.setAttribute( 'aria-expanded', isActive ? 'true' : 'false' );

			if ( isActive ) {
				activeMarker = marker;
			}
		} );

		progressionMarkers.forEach( function ( marker ) {
			var timelineSet = marker.getAttribute( 'data-mrs-timeline-set' );
			var isActive = marker.getAttribute( 'data-season' ) === slug && ( ! timelineSet || timelineSet === 'current' );
			marker.classList.toggle( 'is-active', isActive );
			marker.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
			if ( isActive && marker.closest( '.michiryu-sekki-map__timeline' ) ) {
				centerTimelineMarker( marker );
			}
		} );

		updateProgressionCompass( map, slug );

		paths.forEach( function ( path ) {
			path.classList.toggle( 'is-active', path.getAttribute( 'data-season' ) === slug );
		} );

		details.forEach( function ( detail ) {
			var isActive = detail.getAttribute( 'data-season' ) === slug;
			detail.classList.toggle( 'is-active', isActive );
			detail.hidden = ! isActive;
		} );

		stories.forEach( function ( story ) {
			var isActive = story.getAttribute( 'data-season' ) === slug;
			story.hidden = ! isActive;
			if ( isActive ) {
				activeStorySection = story;
			}
		} );

		popovers.forEach( function ( popover ) {
			popover.hidden = true;
		} );

		if ( activeStorySection ) {
			storyTab = activeStorySection.querySelector( '[data-mrs-story-tab].is-active' );

			if ( ! storyTab && slug === map.getAttribute( 'data-current-season' ) ) {
				storyTab = activeStorySection.querySelector( '[data-mrs-story-tab][data-ko="' + map.getAttribute( 'data-current-ko' ) + '"]' );
			}

			if ( ! storyTab ) {
				storyTab = activeStorySection.querySelector( '[data-mrs-story-tab]' );
			}
		}

		if ( storyTab ) {
			selectMapStory( map, storyTab.getAttribute( 'data-story' ) );
		} else {
			updateStoryCharacters( map, '' );
		}

		if ( focusMarker && activeMarker ) {
			activeMarker.focus( { preventScroll: true } );
			activeMarker.scrollIntoView( { block: 'nearest', inline: 'center' } );
		}
	}

	function updateStoryCharacters( map, storyId ) {
		var characters = map.querySelectorAll( '[data-mrs-character]' );
		var popovers = map.querySelectorAll( '[data-mrs-character-popover]' );
		var characterPanel = map.querySelector( '[data-mrs-map-characters]' );
		var visibleCharacters = 0;

		characters.forEach( function ( character ) {
			var isVisible = character.getAttribute( 'data-story' ) === storyId;
			character.classList.toggle( 'is-visible', isVisible );
			character.setAttribute( 'aria-expanded', 'false' );
			if ( isVisible ) {
				visibleCharacters += 1;
			}
		} );

		popovers.forEach( function ( popover ) {
			popover.hidden = true;
		} );

		if ( characterPanel ) {
			characterPanel.classList.toggle( 'has-visible-characters', visibleCharacters > 0 );
		}
	}

	function selectMapStory( map, storyId ) {
		var activeSection;
		var activeTab = null;
		var activeKo = '';

		if ( ! map || ! storyId ) {
			return;
		}

		activeSection = map.querySelector( '.michiryu-sekki-map__stories:not([hidden])' );

		map.querySelectorAll( '[data-mrs-story-tab]' ).forEach( function ( tab ) {
			var isActive = tab.getAttribute( 'data-story' ) === storyId;
			tab.classList.toggle( 'is-active', isActive );
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
			tab.setAttribute( 'tabindex', isActive ? '0' : '-1' );
			if ( isActive ) {
				activeTab = tab;
				activeKo = tab.getAttribute( 'data-ko' ) || '';
			}
		} );

		if ( activeSection ) {
			activeSection.querySelectorAll( '[data-mrs-story]' ).forEach( function ( story ) {
				var isActive = story.getAttribute( 'data-story' ) === storyId;
				story.classList.toggle( 'is-active', isActive );
				story.hidden = ! isActive;
				if ( isActive && ! activeKo ) {
					activeKo = story.getAttribute( 'data-ko' ) || '';
				}
			} );
		}

		map.querySelectorAll( '[data-mrs-map-ko-details]' ).forEach( function ( details ) {
			var activeDetail = null;

			details.querySelectorAll( '[data-ko]' ).forEach( function ( detail ) {
				var isActive = ( activeKo && detail.getAttribute( 'data-ko' ) === activeKo ) || detail.getAttribute( 'data-story' ) === storyId;
				detail.classList.toggle( 'is-active', isActive );
				detail.hidden = ! isActive;
				if ( isActive ) {
					activeDetail = detail;
				}
			} );

			if ( ! activeDetail ) {
				activeDetail = details.querySelector( '[data-ko]' );
				if ( activeDetail ) {
					activeDetail.classList.add( 'is-active' );
					activeDetail.hidden = false;
				}
			}
		} );

		updateStoryCharacters( map, storyId );

		if ( activeTab ) {
			activeTab.scrollIntoView( { block: 'nearest', inline: 'center' } );
		}
	}

	function stepMapStory( button ) {
		var map = getMapRoot( button );
		var targetSeason = button.getAttribute( 'data-mrs-story-season' );
		var targetStory = button.getAttribute( 'data-mrs-story-target' );
		var activeSection = map ? map.querySelector( '.michiryu-sekki-map__stories:not([hidden])' ) : null;
		var tabs = activeSection ? Array.prototype.slice.call( activeSection.querySelectorAll( '[data-mrs-story-tab]' ) ) : [];
		var activeIndex = tabs.findIndex( function ( tab ) {
			return tab.classList.contains( 'is-active' );
		} );
		var direction = parseInt( button.getAttribute( 'data-mrs-story-step' ), 10 ) || 1;
		var nextIndex;
		var targetPanel;

		if ( targetSeason && targetStory ) {
			selectMapSeason( map, targetSeason, true );
			selectMapStory( map, targetStory );
			targetPanel = map.querySelector( '[data-mrs-story][data-story="' + targetStory + '"]' );
			if ( targetPanel ) {
				targetPanel.focus( { preventScroll: true } );
				targetPanel.scrollIntoView( {
					block: window.matchMedia( '(max-width: 640px)' ).matches ? 'start' : 'nearest',
				} );
			}
			return;
		}

		if ( ! tabs.length ) {
			return;
		}

		nextIndex = ( activeIndex + direction + tabs.length ) % tabs.length;
		selectMapStory( map, tabs[ nextIndex ].getAttribute( 'data-story' ) );
	}

	function moveStoryTabFocus( tab, key ) {
		var map = getMapRoot( tab );
		var tablist = tab.closest( '[role="tablist"]' );
		var tabs = tablist ? Array.prototype.slice.call( tablist.querySelectorAll( '[data-mrs-story-tab]' ) ) : [];
		var activeIndex = tabs.indexOf( tab );
		var nextIndex = activeIndex;
		var nextTab;

		if ( ! tabs.length || activeIndex < 0 ) {
			return;
		}

		if ( key === 'ArrowRight' || key === 'ArrowDown' ) {
			nextIndex = ( activeIndex + 1 ) % tabs.length;
		} else if ( key === 'ArrowLeft' || key === 'ArrowUp' ) {
			nextIndex = ( activeIndex - 1 + tabs.length ) % tabs.length;
		} else if ( key === 'Home' ) {
			nextIndex = 0;
		} else if ( key === 'End' ) {
			nextIndex = tabs.length - 1;
		}

		nextTab = tabs[ nextIndex ];
		if ( nextTab ) {
			nextTab.focus();
			selectMapStory( map, nextTab.getAttribute( 'data-story' ) );
		}
	}

	function setMapZoom( map, nextZoom ) {
		var canvas = map ? map.querySelector( '[data-mrs-map-canvas]' ) : null;
		var zoom = Math.max( 1, Math.min( 2.4, nextZoom ) );

		if ( ! canvas ) {
			return;
		}

		map.setAttribute( 'data-zoom', zoom.toFixed( 2 ) );
		map.classList.toggle( 'is-map-zoomed', zoom > 1 );
		if ( zoom <= 1 ) {
			map._mrsMapPan = { x: 0, y: 0 };
		}
		applyMapTransform( map );
	}

	function getMapZoom( map ) {
		if ( ! map ) {
			return 1;
		}

		return parseFloat( map.getAttribute( 'data-zoom' ) || '1' ) || 1;
	}

	function getMapPan( map ) {
		if ( ! map || ! map._mrsMapPan ) {
			return { x: 0, y: 0 };
		}

		return map._mrsMapPan;
	}

	function clampMapPan( map, pan ) {
		var viewport = map ? map.querySelector( '[data-mrs-map-viewport]' ) : null;
		var canvas = map ? map.querySelector( '[data-mrs-map-canvas]' ) : null;
		var zoom = getMapZoom( map );
		var minX;
		var minY;

		if ( ! viewport || ! canvas || zoom <= 1 ) {
			return { x: 0, y: 0 };
		}

		minX = Math.min( 0, viewport.clientWidth - ( canvas.offsetWidth * zoom ) );
		minY = Math.min( 0, viewport.clientHeight - ( canvas.offsetHeight * zoom ) );

		return {
			x: Math.max( minX, Math.min( 0, pan.x ) ),
			y: Math.max( minY, Math.min( 0, pan.y ) ),
		};
	}

	function applyMapTransform( map ) {
		var canvas = map ? map.querySelector( '[data-mrs-map-canvas]' ) : null;
		var zoom = getMapZoom( map );
		var pan;

		if ( ! canvas ) {
			return;
		}

		pan = clampMapPan( map, getMapPan( map ) );
		map._mrsMapPan = pan;
		canvas.style.transform = 'translate(' + pan.x + 'px, ' + pan.y + 'px) scale(' + zoom + ')';
	}

	function resetMapView( map ) {
		var viewport = map ? map.querySelector( '[data-mrs-map-viewport]' ) : null;

		if ( viewport ) {
			viewport.scrollLeft = 0;
			viewport.scrollTop = 0;
		}

		if ( map ) {
			map._mrsMapPan = { x: 0, y: 0 };
			setMapZoom( map, 1 );
		}
	}

	function startMapSettling( map ) {
		if ( ! map ) {
			return;
		}

		if ( map._mrsSettlingTimer ) {
			window.clearTimeout( map._mrsSettlingTimer );
		}

		map.setAttribute( 'data-mrs-settling', 'true' );
		map.classList.add( 'is-settling-current' );

		map._mrsSettlingTimer = window.setTimeout( function () {
			map.removeAttribute( 'data-mrs-settling' );
			map.classList.remove( 'is-settling-current' );
			map._mrsSettlingTimer = null;
		}, 650 );
	}

	function openMapModal( trigger ) {
		var card = trigger.closest( '.michiryu-sekki' );
		var modal = card ? card.querySelector( '[data-mrs-map-modal]' ) : document.querySelector( '[data-mrs-map-modal]' );
		var dialog;
		var map;
		var firstMarker;

		if ( ! modal ) {
			return null;
		}

		modal.hidden = false;
		modal.setAttribute( 'data-open', 'true' );
		modal._mrsTrigger = trigger;
		document.documentElement.classList.add( 'mrs-map-modal-open' );

		dialog = modal.querySelector( '.michiryu-sekki-map-modal__dialog' );
		if ( dialog ) {
			dialog.scrollTop = 0;
		}

		map = modal.querySelector( '[data-mrs-map]' );
		startMapSettling( map );

		firstMarker = modal.querySelector( '[data-mrs-map-marker].is-current, [data-mrs-map-marker]' );
		if ( firstMarker ) {
			firstMarker.focus( { preventScroll: true } );
		}

		window.requestAnimationFrame( function () {
			if ( dialog ) {
				dialog.scrollTop = 0;
			}
			centerActiveTimelines();
		} );

		return modal;
	}

	function focusMapStoryFromTrigger( trigger, modal ) {
		var map = modal ? modal.querySelector( '[data-mrs-map]' ) : null;
		var season = trigger.getAttribute( 'data-season' );
		var storyId = trigger.getAttribute( 'data-story' );

		if ( ! map || ! season || ! storyId ) {
			return;
		}

		selectMapSeason( map, season, false );
		selectMapStory( map, storyId );
	}

	function closeMapModal( modal ) {
		var map = modal ? modal.querySelector( '[data-mrs-map]' ) : null;

		if ( ! modal ) {
			return;
		}

		if ( map && map._mrsSettlingTimer ) {
			window.clearTimeout( map._mrsSettlingTimer );
			map._mrsSettlingTimer = null;
		}
		if ( map ) {
			map.removeAttribute( 'data-mrs-settling' );
			map.classList.remove( 'is-settling-current' );
		}

		modal.hidden = true;
		modal.removeAttribute( 'data-open' );
		document.documentElement.classList.remove( 'mrs-map-modal-open' );

		if ( modal._mrsTrigger ) {
			modal._mrsTrigger.focus( { preventScroll: true } );
		}
	}

	function openLearnModal( trigger ) {
		var targetId = trigger.getAttribute( 'aria-controls' );
		var modal = targetId ? document.getElementById( targetId ) : null;
		var closeButton;

		if ( ! modal ) {
			return;
		}

		modal.hidden = false;
		modal.setAttribute( 'data-open', 'true' );
		modal._mrsTrigger = trigger;
		document.documentElement.classList.add( 'mrs-learn-modal-open' );

		closeButton = modal.querySelector( '[data-mrs-learn-close]' );
		if ( closeButton ) {
			closeButton.focus( { preventScroll: true } );
		}
	}

	function closeLearnModal( modal ) {
		if ( ! modal ) {
			return;
		}

		modal.hidden = true;
		modal.removeAttribute( 'data-open' );
		document.documentElement.classList.remove( 'mrs-learn-modal-open' );

		if ( modal._mrsTrigger ) {
			modal._mrsTrigger.focus( { preventScroll: true } );
		}
	}

	function openCharacterPopover( button ) {
		var map = getMapRoot( button );
		var characterId = button.getAttribute( 'data-character' );
		var season = button.getAttribute( 'data-season' );
		var story = button.getAttribute( 'data-story' );
		var selector = '[data-mrs-character-popover][data-season="' + season + '"][data-story="' + story + '"][data-character="' + characterId + '"]';
		var popover = map ? map.querySelector( selector ) : null;

		if ( ! popover ) {
			return;
		}

		map.querySelectorAll( '[data-mrs-character]' ).forEach( function ( item ) {
			item.setAttribute( 'aria-expanded', item === button ? 'true' : 'false' );
		} );
		map.querySelectorAll( '[data-mrs-character-popover]' ).forEach( function ( item ) {
			item.hidden = item !== popover;
		} );
	}

	function closeCharacterPopover( button ) {
		var map = getMapRoot( button );

		if ( ! map ) {
			return;
		}

		map.querySelectorAll( '[data-mrs-character]' ).forEach( function ( item ) {
			item.setAttribute( 'aria-expanded', 'false' );
		} );
		map.querySelectorAll( '[data-mrs-character-popover]' ).forEach( function ( item ) {
			item.hidden = true;
		} );
	}

	document.addEventListener( 'pointerdown', function ( event ) {
		var viewport = event.target.closest( '[data-mrs-map-viewport]' );
		var map = viewport ? getMapRoot( viewport ) : null;
		var startPan;
		var startX;
		var startY;
		var dragging = false;

		if ( ! viewport || ! map || getMapZoom( map ) <= 1 || event.target.closest( 'button, a, input, select, textarea' ) ) {
			return;
		}

		event.preventDefault();
		startPan = getMapPan( map );
		startX = event.clientX;
		startY = event.clientY;
		map.classList.add( 'is-map-dragging' );
		viewport.setPointerCapture( event.pointerId );

		function moveMap( moveEvent ) {
			var nextPan = {
				x: startPan.x + moveEvent.clientX - startX,
				y: startPan.y + moveEvent.clientY - startY,
			};

			dragging = true;
			map._mrsMapPan = nextPan;
			applyMapTransform( map );
		}

		function stopDrag() {
			map.classList.remove( 'is-map-dragging' );
			viewport.removeEventListener( 'pointermove', moveMap );
			viewport.removeEventListener( 'pointerup', stopDrag );
			viewport.removeEventListener( 'pointercancel', stopDrag );
			if ( dragging ) {
				window.setTimeout( function () {
					dragging = false;
				}, 0 );
			}
		}

		viewport.addEventListener( 'pointermove', moveMap );
		viewport.addEventListener( 'pointerup', stopDrag );
		viewport.addEventListener( 'pointercancel', stopDrag );
	} );

	document.addEventListener( 'click', function ( event ) {
		var previous = event.target.closest( '[data-mrs-carousel-prev]' );
		var next = event.target.closest( '[data-mrs-carousel-next]' );

		if ( previous ) {
			moveTrack( previous, -1 );
		}

		if ( next ) {
			moveTrack( next, 1 );
		}

		var mapOpen = event.target.closest( '[data-mrs-map-open]' );
		var readStoryOpen = event.target.closest( '[data-mrs-read-story-open]' );
		var mapClose = event.target.closest( '[data-mrs-map-close]' );
		var learnOpen = event.target.closest( '[data-mrs-learn-open]' );
		var learnClose = event.target.closest( '[data-mrs-learn-close]' );
		var marker = event.target.closest( '[data-mrs-map-marker]' );
		var selector = event.target.closest( '[data-mrs-map-select]' );
		var zoom = event.target.closest( '[data-mrs-map-zoom]' );
		var reset = event.target.closest( '[data-mrs-map-reset]' );
		var current = event.target.closest( '[data-mrs-map-current]' );
		var character = event.target.closest( '[data-mrs-character]' );
		var characterClose = event.target.closest( '[data-mrs-character-close]' );
		var storyTab = event.target.closest( '[data-mrs-story-tab]' );
		var storyStep = event.target.closest( '[data-mrs-story-step]' );
		var restart = event.target.closest( '[data-mrs-restart-journey]' );

		if ( mapOpen ) {
			var openedModal;
			event.preventDefault();
			openedModal = openMapModal( mapOpen );
			if ( readStoryOpen ) {
				focusMapStoryFromTrigger( readStoryOpen, openedModal );
			}
		}

		if ( mapClose ) {
			closeMapModal( mapClose.closest( '[data-mrs-map-modal]' ) );
		}

		if ( learnOpen ) {
			openLearnModal( learnOpen );
		}

		if ( learnClose ) {
			closeLearnModal( learnClose.closest( '[data-mrs-learn-modal]' ) );
		}

		if ( marker ) {
			var markerMap = getMapRoot( marker );
			if ( markerMap && markerMap.getAttribute( 'data-mrs-settling' ) === 'true' ) {
				event.preventDefault();
				return;
			}
			selectMapSeason( markerMap, marker.getAttribute( 'data-season' ), false );
		}

		if ( selector ) {
			var selectorMap = getMapRoot( selector );
			if ( selectorMap && selectorMap.getAttribute( 'data-mrs-settling' ) === 'true' ) {
				event.preventDefault();
				return;
			}
			selectMapSeason( selectorMap, selector.getAttribute( 'data-mrs-map-select' ), true );
		}

		if ( zoom ) {
			var zoomMap = getMapRoot( zoom );
			var direction = zoom.getAttribute( 'data-mrs-map-zoom' ) === 'in' ? 0.2 : -0.2;
			setMapZoom( zoomMap, getMapZoom( zoomMap ) + direction );
		}

		if ( reset ) {
			resetMapView( getMapRoot( reset ) );
		}

		if ( current ) {
			var currentMap = getMapRoot( current );
			selectMapSeason( currentMap, currentMap.getAttribute( 'data-current-season' ), true );
		}

		if ( storyTab ) {
			selectMapStory( getMapRoot( storyTab ), storyTab.getAttribute( 'data-story' ) );
		}

		if ( storyStep ) {
			stepMapStory( storyStep );
		}

		if ( restart ) {
			restartJourney( restart );
		}

		if ( character ) {
			openCharacterPopover( character );
		}

		if ( characterClose ) {
			closeCharacterPopover( characterClose );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var modal;
		var storyTab = event.target.closest( '[data-mrs-story-tab]' );

		if ( storyTab && [ 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End' ].indexOf( event.key ) !== -1 ) {
			event.preventDefault();
			moveStoryTabFocus( storyTab, event.key );
			return;
		}

		modal = getOpenModal();
		if ( modal && event.key === 'Tab' ) {
			trapModalFocus( event, modal );
			return;
		}

		if ( event.key === 'Escape' ) {
			document.querySelectorAll( '[data-mrs-character-popover]:not([hidden])' ).forEach( function ( popover ) {
				popover.hidden = true;
			} );
			document.querySelectorAll( '[data-mrs-character]' ).forEach( function ( character ) {
				character.setAttribute( 'aria-expanded', 'false' );
			} );

			modal = document.querySelector( '[data-mrs-map-modal][data-open="true"]' );
			if ( modal ) {
				closeMapModal( modal );
			}

			modal = document.querySelector( '[data-mrs-learn-modal][data-open="true"]' );
			if ( modal ) {
				closeLearnModal( modal );
			}

			modal = document.querySelector( '[data-mrs-story-modal][data-open="true"]' );
			if ( modal ) {
				var closeLink = modal.querySelector( '.michiryu-sekki-story-reader__close' );
				if ( closeLink ) {
					window.location.href = closeLink.href;
				}
			}
		}
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			window.requestAnimationFrame( focusCurrentSlides );
			window.requestAnimationFrame( centerActiveTimelines );
			initializeStoryReaderProgress();
		} );
	} else {
		window.requestAnimationFrame( focusCurrentSlides );
		window.requestAnimationFrame( centerActiveTimelines );
		initializeStoryReaderProgress();
	}

	window.addEventListener( 'load', focusCurrentSlides );
	window.addEventListener( 'load', centerActiveTimelines );

	document.addEventListener( 'contextmenu', function ( event ) {
		if ( event.target.closest( '.michiryu-sekki-image-wrap' ) ) {
			event.preventDefault();
		}
	} );

	document.addEventListener( 'dragstart', function ( event ) {
		if ( event.target.closest( '.michiryu-sekki-image-wrap' ) ) {
			event.preventDefault();
		}
	} );
}() );
