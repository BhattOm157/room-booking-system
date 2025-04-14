$(document).ready(function() {
    // Load rooms
    function loadRooms() {
        $.ajax({
            url: 'api/get_rooms.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#rooms-container').html(response.html);
                    
                    // Attach event listeners to book buttons
                    $('.book-now-btn').click(function() {
                        const roomId = $(this).data('room-id');
                        const roomName = $(this).data('room-name');
                        
                        // Start booking process
                        startBooking(roomId, roomName);
                    });
                } else {
                    showAlert('danger', 'Error loading rooms: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error connecting to server. Please try again.');
            }
        });
    }
    
    // Start booking process
    function startBooking(roomId, roomName) {
        // Show booking modal
        $('#booking-room-name').text(roomName);
        $('#booking-room-id').val(roomId);
        $('#booking-step-1').show();
        $('#booking-step-2').hide();
        $('#booking-step-3').hide();
        $('#bookingModal').modal('show');
        
        // Check if user is in queue or can book immediately
        checkBookingStatus(roomId);
    }
    
    // Check booking status
    function checkBookingStatus(roomId) {
        $.ajax({
            url: 'api/check_queue.php',
            type: 'GET',
            data: {
                room_id: roomId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.can_book) {
                        // User can book immediately
                        $('#booking-step-1').hide();
                        $('#booking-step-2').show();
                        $('#booking-session-id').val(response.session_id);
                        
                        // Start countdown timer
                        startBookingTimer(response.expires_at);
                    } else {
                        // User is in queue
                        $('#queue-position').text(response.queue_position);
                        $('#queue-message').text(response.message);
                        
                        // Poll for queue status
                        if (response.queue_position > 0) {
                            setTimeout(function() {
                                checkBookingStatus(roomId);
                            }, 5000); // Check every 5 seconds
                        }
                    }
                } else {
                    showAlert('danger', 'Error checking booking status: ' + response.message);
                    $('#bookingModal').modal('hide');
                }
            },
            error: function() {
                showAlert('danger', 'Error connecting to server. Please try again.');
                $('#bookingModal').modal('hide');
            }
        });
    }
    
    // Start booking timer
    function startBookingTimer(expiresAt) {
        const expiresAtDate = new Date(expiresAt);
        
        // Update timer every second
        const timerInterval = setInterval(function() {
            const now = new Date();
            const diffMs = expiresAtDate - now;
            
            if (diffMs <= 0) {
                // Timer expired
                clearInterval(timerInterval);
                $('#timer-text').text('Time expired!');
                $('#booking-form-submit').prop('disabled', true);
                showAlert('danger', 'Your booking session has expired. Please try again.');
                $('#bookingModal').modal('hide');
                
                // Reload rooms to reflect status changes
                loadRooms();
            } else {
                // Update timer text
                const diffSeconds = Math.floor(diffMs / 1000);
                $('#timer-text').text(diffSeconds + ' seconds remaining');
            }
        }, 1000);
        
        // Store interval ID to clear it when modal is closed
        $('#bookingModal').data('timer-interval', timerInterval);
    }
    
    // Complete booking
    $('#booking-form').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'complete_booking',
            room_id: $('#booking-room-id').val(),
            session_id: $('#booking-session-id').val(),
            user_name: $('#booking-name').val(),
            mobile: $('#booking-mobile').val(),
            email: $('#booking-email').val()
        };
        
        $.ajax({
            url: 'api/process_booking.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show confirmation
                    $('#booking-step-2').hide();
                    $('#booking-step-3').show();
                    $('#booking-confirmation-id').text(response.booking_id);
                    
                    // Clear form
                    $('#booking-form')[0].reset();
                    
                    // Reload rooms after a brief delay
                    setTimeout(function() {
                        loadRooms();
                    }, 2000);
                } else {
                    showAlert('danger', 'Error completing booking: ' + response.message);
                    $('#bookingModal').modal('hide');
                }
            },
            error: function() {
                showAlert('danger', 'Error connecting to server. Please try again.');
                $('#bookingModal').modal('hide');
            }
        });
    });
    
    // Close booking modal cleanup
    $('#bookingModal').on('hidden.bs.modal', function() {
        // Clear timer if exists
        const timerInterval = $(this).data('timer-interval');
        if (timerInterval) {
            clearInterval(timerInterval);
        }
    });
    
    // Show alert
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('#alert-container').html(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Initial load
    loadRooms();
});