
var reload = false; // do not change this

$(document).ready( function( ) {

	// TODO: items on the page that need to be redrawn:
	// the main board
	// the move info
	// the extra info
	// the player info

	// TODO: things that need to be set using jQuery on( ):
	// the Game Info button
	// player card clicks ?

	// TODO: actions to take:
	// show review buttons
	//		use multiple buttons? or checkboxes for speed options
	//			- each player as whole - skip to next N action
	//			- each state as whole - skip to next action that is not this action (A and O are same)
	//			- each action - show individual actions
	// when review button is clicked
	// disable review buttons
	// show loading throbber, or dice loader gif ?
	// calc which step to go to based on speed settings
	// set new JS step value
	// ajax and get new game info and board and move info and player info
	// 		- session['step'] gets updated in back-end
	// redraw new board, game_info, player info and move info without refresh
	// enable review buttons
	// if 'REFRESH' is returned, do that instead

	// TODO: things that need to be made DRY
	// conquer limit table
	// trade value table
	// game info table (right side of main game info box, if it's used on the join page)


	// card click function
	$('#players').find('li').css('cursor', 'pointer').click( function( ) {
		var id = $(this).attr('id').slice(2);

		if (debug) {
			window.location = 'review_ajax_helper.php'+debug_query+'&'+'cardcheck=1&id='+id;
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'review_ajax_helper.php',
			data: 'cardcheck=1&id='+id,
			success: function(msg) {
				alert(msg);
			}
		});

		return false;
	});


	// tha fancybox stuff
	$("a.fancybox").fancybox({
		autoSize : false,
		width : '80%',
		height : '80%',
		beforeLoad : function( ) {
			if ( ! this.element.parent( ).is('#history')) {
				$('#game_info').show( );
			}
		},
		afterClose : function( ) {
			$('#game_info').hide( );
		}
	});


	// review button clicks
	$('#controls').on('click', 'span.button:not(.disabled)', function(evt) {
console.log($.extend({}, evt));
console.log($.extend({}, this));
		// disable the buttons
		$('#controls').find('.prev').add('.next').addClass('disabled');

		var $tgt = $(evt.currentTarget);

		var next = $tgt.is('.next');
		var type = $.trim($tgt.attr('class').replace(/button|next|prev|disabled/ig, ''));

		step = get_step(type, next);

		if (debug) {
			window.location = 'review_ajax_helper.php'+ debug_query +'&step='+ step +'&file='+ game_file;
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'review_ajax_helper.php',
			data: {
				'step': step,
				'file': game_file
			},
			success: function(msg) {
console.log(msg);
				// if something happened, just reload
				if ('{' != msg[0]) {
					if (reload) { window.location.reload( ); } else { alert('Reload 1'); }
					return;
				}

				var reply = JSON.parse(msg);
console.log(reply);

				if (reply.error) {
					alert(reply.error);
					if (reload) { window.location.reload( ); } else { alert('Reload 2'); }
					return;
				}

				if ('RELOAD' == reply.action) {
					if (reload) { window.location.reload( ); } else { alert('Reload 3'); }
					return;
				}

				// update the token
				$('#token').val(reply.token);

				// redraw
				$('#board').find('> span').remove( ).end( ).append(reply.board);
				$('#dice').empty( ).append(reply.dice);
				$('#game_info').replaceWith(reply.$game_info);
				$('#move_info').empty( ).append(reply.move_info);
				$('#players').replaceWith(reply.players);

				$('div.steps').text(step +' / '+ (steps.length - 1));
				update_buttons( );
			},
			error: function( ) {
				if (reload) { window.location.reload( ); } else { alert('Reload 4'); }
				return;
			}
		});
	});


	function get_step(type, next) {
		if ('undefined' === typeof next) {
			next = true;
		}

		var cur_step_type = steps[step].charAt(0),
			next_step = step + (next ? 1 : -1),
			next_step_type = steps[next_step].charAt(0),
			this_step,
			this_step_type;

		// search through the steps for the next step
		switch (type) {
			// the immediate prev/next state, skipping all
			// that are the same as the current state
			case 'state' :
				while (next_step_type === cur_step_type) {
					next_step += (next ? 1 : -1);
					next_step_type = steps[next_step].charAt(0);
				}

				// when going backwards, go to the beginning of the state, not the end
				if ( ! next) {
					this_step = next_step;
					this_step_type = next_step_type;

					while (this_step_type === next_step_type) {
						this_step -= 1;
						this_step_type = steps[this_step].charAt(0);
					}

					// add one because the first state action got passed
					next_step = this_step + 1;
				}
				break;

			// the immediate prev/next player
			case 'player' :
				while ('N' !== next_step_type) {
					next_step += (next ? 1 : -1);
					next_step_type = steps[next_step].charAt(0);
				}
				break;

			// the immediate prev/next action
			case 'action' :
			default :
				// do nothing, it's been taken care of above
				break;
		}

		if (0 > next_step) {
			next_step = 0;
		}

		if ((steps.length + 1) < next_step) {
			next_step = steps.length + 1;
		}
console.log('next step = '+step);
console.log('cur type = '+cur_step_type);
console.log('next type = '+steps[next_step].charAt(0));

		return next_step;
	}


	// update review button states
	function update_buttons( ) {
console.log('updating buttons');
		var $controls = $('#controls');

		$controls.find('.prev').add('.next').removeClass('disabled');

		if (0 === step) {
			$controls.find('.prev').addClass('disabled');
		}
		else if (step === (steps.length - 1)) {
			$controls.find('.next').addClass('disabled');
		}
	}
	update_buttons( );

});
