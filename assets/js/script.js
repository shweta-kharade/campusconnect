// CampusConnect JavaScript

$(document).ready(function() {
    console.log('CampusConnect loaded!');
    
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 3000);
    
    // Like functionality
    $(document).on('click', '.like-btn', function(e) {
        e.preventDefault();
        let btn = $(this);
        let postId = btn.data('post-id');
        let likeIcon = btn.find('i');
        let likeCount = btn.find('.like-count');
        
        console.log('Liking post:', postId);
        
        $.ajax({
            url: '/campusconnect/actions/like_post.php',
            method: 'POST',
            data: { post_id: postId },
            dataType: 'json',
            success: function(response) {
                console.log('Like response:', response);
                if (response.success) {
                    if (response.liked) {
                        likeIcon.removeClass('bi-heart').addClass('bi-heart-fill');
                        likeIcon.css('color', '#dc3545');
                    } else {
                        likeIcon.removeClass('bi-heart-fill').addClass('bi-heart');
                        likeIcon.css('color', '#666');
                    }
                    likeCount.text(response.likes_count);
                    showToast(response.liked ? 'Post liked!' : 'Post unliked', 'success');
                } else {
                    showToast('Error: ' + (response.message || 'Something went wrong'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error liking post. Please try again.', 'error');
            }
        });
    });
    
    // Save/Bookmark functionality
    $(document).on('click', '.save-btn', function(e) {
        e.preventDefault();
        let btn = $(this);
        let itemId = btn.data('post-id');
        let itemType = btn.data('type') || 'post';
        
        console.log('Saving item:', itemId, itemType);
        
        $.ajax({
            url: '/campusconnect/actions/save_item.php',
            method: 'POST',
            data: { 
                item_id: itemId, 
                item_type: itemType 
            },
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    if (response.saved) {
                        btn.html('<i class="bi bi-bookmark-check-fill"></i> Saved');
                        showToast('Saved to bookmarks!', 'success');
                    } else {
                        btn.html('<i class="bi bi-bookmark"></i> Save');
                        showToast('Removed from bookmarks', 'info');
                    }
                } else {
                    showToast('Error: ' + (response.message || 'Something went wrong'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showToast('Error saving item. Please try again.', 'error');
            }
        });
    });
    
    // Comment button
    // $(document).on('click', '.comment-btn', function(e) {
    //     e.preventDefault();
    //     let postId = $(this).data('post-id');
    //     // You can implement comment modal here
    //     showToast('Comment feature coming soon!', 'info');
    // });
    
    // Infinite scroll
    let loading = false;
    let page = 2;
    let hasMore = true;
    
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            if (!loading && hasMore) {
                loading = true;
                loadMorePosts();
            }
        }
    });
    
    function loadMorePosts() {
        $('#feed-container').append('<div class="text-center my-3" id="loader"><div class="spinner-border text-primary" role="status"></div></div>');
        
        $.ajax({
            url: '/campusconnect/actions/load_more.php',
            method: 'GET',
            data: { page: page },
            success: function(response) {
                $('#loader').remove();
                if (response && response.trim() !== '') {
                    $('#feed-container').append(response);
                    page++;
                    loading = false;
                } else {
                    hasMore = false;
                    $('#feed-container').append('<div class="text-center text-muted my-3" id="no-more-posts">No more posts to load</div>');
                }
            },
            error: function() {
                $('#loader').remove();
                loading = false;
                showToast('Error loading more posts', 'error');
            }
        });
    }
});

// Toast notification function
function showToast(message, type = 'success') {
    let bgColor = type === 'success' ? '#057642' : (type === 'error' ? '#dc3545' : '#0A66C2');
    let icon = type === 'success' ? '✓' : (type === 'error' ? '✗' : 'ℹ');
    
    let toast = $(`
        <div class="toast-notification" style="position: fixed; bottom: 20px; right: 20px; 
                    background: ${bgColor}; color: white; padding: 12px 24px; 
                    border-radius: 8px; z-index: 9999; animation: fadeIn 0.3s;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            ${icon} ${message}
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(function() {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// Add fade-in animation to CSS if not present
if (!$('#fadeInStyle').length) {
    $('head').append(`
        <style id="fadeInStyle">
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .fade-in {
                animation: fadeIn 0.5s ease;
            }
        </style>
    `);
}