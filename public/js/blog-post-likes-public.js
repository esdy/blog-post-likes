 jQuery(function($){
	'use strict';
	//like post top
	$('#like-post').click(function(){
		let post_id = $('#like-post').attr('value');
		postLike(post_id,1);

	});
	
	//dislike post top
	$('#dislike-post').click(function(){
		let post_id = $('#dislike-post').attr('value');
		postLike(post_id,0);

	});	
	
	//like post bottom
	$('#like-post-bottom').click(function(){
		let post_id = $('#like-post-bottom').attr('value');
		postLike(post_id,1);

	});
	
	//dislike post bottom
	$('#dislike-post-bottom').click(function(){
		let post_id = $('#dislike-post-bottom').attr('value');
		postLike(post_id,0);

	});		
	
	const postLike = (post_id,value) => {
		$.post({
			type: 'POST',
			data: {
					action: 'ewbpl_update_likes',
					value: value,
					post_id: post_id
			},
			url: my_ajax_object.ajaxurl
		}).done(function(data){
			let post_likes = JSON.parse(data);
			$('#post-likes').html(post_likes.likes);
			$('#post-likes-bottom').html(post_likes.likes);
			$('#post-dislikes').html(post_likes.dislikes);
			$('#post-dislikes-bottom').html(post_likes.dislikes);
			if(post_likes.liked == 1){
				$('.liked-post').css('color','red');
				$('.disliked-post').css('color','black');
				$('.liked-post-bottom').css('color','red');
				$('.disliked-post-bottom').css('color','black');				
			}else if(post_likes.liked == 0){
				$('.liked-post').css('color','black');
				$('.disliked-post').css('color','red');
				$('.liked-post-bottom').css('color','black');
				$('.disliked-post-bottom').css('color','red');				
			}
		})		
	}
 });