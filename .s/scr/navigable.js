/**
 *
 * '||''|.                            '||
 *  ||   ||    ....  .... ...   ....   ||    ...   ... ...  ... ..
 *  ||    || .|...||  '|.  |  .|...||  ||  .|  '|.  ||'  ||  ||' ''
 *  ||    || ||        '|.|   ||       ||  ||   ||  ||    |  ||
 * .||...|'   '|...'    '|     '|...' .||.  '|..|'  ||...'  .||.
 *                                                  ||
 * --------------- By Display:inline ------------- '''' -----------
 *
 * Navigable menus plugin
 *
 * Structural good practices from the article from Addy Osmani 'Essential jQuery plugin patterns'
 * @url http://coding.smashingmagazine.com/2011/10/11/essential-jquery-plugin-patterns/
 */

/*
 * The semi-colon before the function invocation is a safety
 * net against concatenated scripts and/or other plugins
 * that are not closed properly.
 */
;(function($, window, document)
{
	/*
	 * window and document are passed through as local variables rather than as globals, because this (slightly)
	 * quickens the resolution process and can be more efficiently minified.
	 */

		// Objects cache
	var doc = $(document),

		// Global animation switch
		animate = true;

	// Navigable menus
	doc.on('click', '.navigable li, .navigable li > span, .navigable li > a, .navigable li > b', function(event)
	{
		// Only work if the element is the event's target
		if (event.target !== this)
		{
			return;
		}

			// Clicked element
		var clicked = $(this),

			// LI element
			li = $(this).closest('li'),

			// Sub-menu
			submenu = li.children('ul:first'),

			// List of all ul above the current li
			allUL = li.parentsUntil('.navigable', 'ul'),

			// Current li ul
			parentUL = allUL.eq(0),

			// Main ul
			mainUL = allUL.eq(-1),

			// Root navigable element
			root = mainUL.closest('.navigable'),

			// Settings
			settings = $.extend({}, $.template.navigable, root.data('navigable-options')),

			// Back button
			back = root.children('.back'),
			backText,

			// Load indicator
			load = root.children('.load'),

			// Other vars
			current, url, delayedOpen, text, hidden, parentLi, parentLink;

		// Prepare on first call (Подготовьтесь по первому вызову)
		if (!mainUL.hasClass('fixed'))
		{
			root.height(mainUL.outerHeight(true));
			mainUL.addClass('fixed');
			
			
			
		}

		// Create back button if needed (При необходимости создайте кнопку "назад")
		if (back.length === 0)
		{
			// Text
			text = settings.backText || '&nbsp;';
			// Create element
			back = $('<div class="back"><span class="back-arrow"></span><span class="back-text">'+text+'</span></div>').prependTo(root).click(function(event)
			{
				var current = root.data('navigableCurrent'),
					target, left, backHeight, parentLi, parentLink;

				// If no current element, we're already at the top level
				if (!current)
				{
					
					
					
					back.stop(true)[animate ? 'animate' : 'css']({ marginTop: -back.outerHeight()+'px' });
					return;
				}
				
				
				// Get parent target
				target = current.parent().closest('ul');

				// Check if working on the main UL
				if (target.hasClass('fixed'))
				{
					left = 0;
					backHeight = 0;
					root.removeData('navigableCurrent');
					back.stop(true)[animate ? 'animate' : 'css']({ marginTop: -back.outerHeight()+'px' });
				
				    
				    
				    
				    
				}
				else
				{
					// Text
					if (settings.backText)
					{
						backText.text(settings.backText);
						
						
					}
					else
					{
						parentLi = target.closest('li');
						parentLink = parentLi.children('a, b, span').not('.icon').first();
						if (!parentLink.length)
						{
							parentLink = parentLi;
							
							
							
							
							
						}
						backText.text(parentLink.contents().filter(function(){ return(this.nodeType == 3); }).text() );
					}

					left = -target.parentsUntil('.navigable', 'ul').length*100;
					backHeight = back.outerHeight();
					root.data('navigableCurrent', target);
				}

				// Set root element size according to target size
				root.stop(true)[animate ? 'animate' : 'css']({ height: (target.outerHeight(true)+backHeight)+'px' });

				// Move whole navigation to reveal target ul
				mainUL.stop(true)[animate ? 'animate' : 'css']({ left: left+'%' });

				// Send close event
				current.parent().closest('li').trigger('navigable-close');
			});

			// Hide it
			back.css({ marginTop: -back.outerHeight()+'px' });
		}

		// Button
		backText = back.children('.back-text');
//admin.ViewHomePageModule();













		// If there is a load indicator on, remove it first (Если горит индикатор загрузки, сначала снимите его)
		if (load.length > 0)
		{
			// Currently displayed UL
			current = root.data('navigableCurrent') || mainUL;

			// Animation and callback
			mainUL.stop(true)[animate ? 'animate' : 'css']({ left: -(current.parentsUntil('.navigable', 'ul').length*100)+'%' }, 'fast');
			load.stop(true).removeData('navigable-target');
			if (animate)
			{
				load.animate({ right: '-10%' }, 'fast', function()
				{
					load.remove();
					clicked.click();
				});

				// Prevent default behavior
				event.preventDefault();

				// Done for now
				return;
			}
			else
			{
				load.remove();
			}
		}

		// If there is a submenu (Если есть подменю)
		if (submenu.length > 0)
		{
		    
		   
		            
				
		    
		    
		    
			// Reveal hidden parents if needed for correct height processing (При необходимости покажите скрытых родителей для правильной обработки высоты)
			hidden = root.tempShow();

			// If not ready yet (Если еще не готовы)
			if (parentUL.outerHeight(true) === 0 && allUL.length < 3)
			{
				// Delay action (Задержка действия)
				delayedOpen = function()
				{
					if (parentUL.outerHeight(true) > 0)
					{
						animate = false;
						clicked.click();
						animate = true;
					}
					else
					{
						setTimeout(delayedOpen, 40);
					}
					
					
					
				};
				setTimeout(delayedOpen, 40);
				return;
			}

			// Set as current (Установить как текущий)
			root.data('navigableCurrent', submenu);

			// Hide previously open submenus (Скрыть ранее открытые подменю)
			parentUL.find('ul').hide();

			// Display parent menus (Отображение родительских меню)
			allUL.show();

			// Display it
			submenu.show();

			// Correct position if needed (При необходимости откорректируйте положение.)
			submenu.add(allUL.not(':last')).each(function(i)
			{
				var menu = $(this),
					parent = menu.parent();

				if ($.inArray(parent.css('position'), ['relative', 'absolute']) > -1)
				{
					menu.css('top', -parent.position().top+'px');
				}
			});

			/*
			 * Animation
			 */

			// Text
			if (settings.backText)
			{
				backText.text(settings.backText);
			}
			else
			{
				parentLi = li;
				parentLink = parentLi.children('a, b, span').not('.icon').first();
				
				
				
				
				if (!parentLink.length)
				{
					parentLink = parentLi;
					
				
				}
				backText.text(parentLink.contents().filter(function(){ return(this.nodeType == 3); }).text() );
				
				
				
				
				
				
			}


			
			
			
			
			
			// Set root element size according to target size
			root.stop(true).height(parentUL.outerHeight(true)+back.outerHeight(true))[animate ? 'animate' : 'css']({ height: (submenu.outerHeight(true)+back.outerHeight())+'px' });

			// Move whole navigation to reveal target ul
			mainUL.stop(true)[animate ? 'animate' : 'css']({ left: -(allUL.length*100)+'%' });

			// Show back button
			back[animate ? 'animate' : 'css']({ marginTop: 0 });

			// Send open event
			li.trigger('navigable-open');

			// Hide previously hidden parents (Скрыть ранее скрытых родителей)
			hidden.tempShowRevert(); 

			// Prevent default behavior
			event.preventDefault();
		}
		else if (clicked.hasClass('navigable-ajax'))
		{
			// Get target url
			url = clicked.is('a') ? clicked.attr('href') : clicked.data('navigable-url');

			// If valid
			if (url && typeof url === 'string' && $.trim(url).length > 0 && url.substr(0, 1) !== '#')
			{
				// Load indicator
				load = $('<div class="load" style="right: -10%"></div>').appendTo(root);

				// Mémorise the current element in the load indicator, so in case of concurrent loads, only the last one gets open
				load.data('navigable-target', this);

				// Move whole navigation to reveal load indicator
				mainUL.stop(true)[animate ? 'animate' : 'css']({ left: -((allUL.length-1)*100+10)+'%' });

				// Show load
				load[animate ? 'animate' : 'css']({ right: '0%' });

				// Load submenu
				$.ajax(url, {
					error: function(jqXHR, textStatus, errorThrown)
					{
						// Refresh load notification since it may have changed since the request was sent
						var load = root.children('.load'),
							current;

						// If notification system is enabled
						if (window.notify)
						{
							window.notify('Menu loading failed with the status "'+textStatus+'"');
						}

						// If related load is still here
						if (load.length > 0 && load.data('navigable-target') === clicked[0])
						{
							// Currently displayed UL
							current = root.data('navigableCurrent') || mainUL;

							// Animation and callback
							mainUL.stop(true)[animate ? 'animate' : 'css']({ left: -(current.parentsUntil('.navigable', 'ul').length*100)+'%' }, 'fast');
							load.stop(true).removeData('navigable-target');
							if (animate)
							{
								load.animate({ right: '-10%' }, 'fast', function()
								{
									// Remove load, not needed anymore
									load.remove();
								});
							}
							else
							{
								load.remove();
							}
						}
					},
					success: function(data, textStatus, jqXHR)
					{
						// Refresh load notification since it may have changed since the request was sent
						var load = root.children('.load');

						// Remove ajax marker, mark as loaded
						clicked.removeClass('navigable-ajax').addClass('navigable-ajax-loaded');

						// Append data
						li.append(data);

						// If related load is still here
						if (load.length > 0 && load.data('navigable-target') === clicked[0])
						{
							// Remove load, not needed anymore
							load.remove();

							// Finally open the clicked element
							clicked.click();
						}

						// Trigger notification
						clicked.trigger('navigable-ajax-loaded');
					}
				});

				// Prevent default behavior
				event.preventDefault();
			}
		}
		else if (clicked.hasClass('navigable-ajax-loaded'))
		{
			// Probably an ajax menu who loaded nothing, prevent default behavior
			event.preventDefault();
		}
	});

	/**
	 * Reset navigable position to main menu (Сброс навигационного положения в главное меню)
	 */
	$.fn.navigableReset = function()
	{
		this.filter('.navigable').each( function(i)
		{
				// Navigable element
			var root = $(this),

				// Back button
				back = root.children('.back'),

				// Hidden parents
				hidden;

			// If valid
			if (back.length > 0)
			{
				// Reveal hidden parents if needed for correct height processing
				hidden = root.tempShow();


				// Walk back the arbo
				while (root.data('navigableCurrent'))
				{
					back.click();
					
				}

				// Hide previously hidden parents
				hidden.tempShowRevert();
			}
		});

		return this;
	};

	// Add to template setup function (Добавить в шаблон функцию настройки)
	$.template.addSetupFunction(function(self, children)
	{
		// Current open menu element (Текущий открытый элемент меню)
		this.findIn(self, children, '.navigable-current').each(function(i)
		{
			var closest = $(this).closest('ul').closest('li, .navigable'),
				child;

			// Check if in a submenu (Проверьте, находится ли в подменю)
			if (closest.length > 0 && !closest.hasClass('navigable'))
			{
				// Disable animation
				animate = false;



				// Is there a span or a link? (Есть ли здесь промежуток или ссылка?)
				child = closest.children('a, span').first();
				if (child.length > 0)
				{
					child.click();
				}
				else
				{
					closest.click();
				}

				// Enable animation
				animate = true;
			}
			
			
			//admin.ViewHomePageModule();
			
			
		});

		return this;
	});

	/**
	 * Navigable menu defaults
	 * @var object
	 */
	$.template.navigable = {

		/**
		 * Text of the back button, or false to use the parent element's text
		 * @var string|boolean
		 */
		backText: false
	};

})(jQuery, window, document);