<script>
    let chatInterval;

    function openChatPopup(id, bill_no) {
        $('#bill_ids').val(id);
        $('#bill_number').text(bill_no);
        let groupChatModal = new bootstrap.Modal(document.getElementById('groupChatModal'));
        groupChatModal.show();

        // Start polling messages
        if (chatInterval) clearInterval(chatInterval);
        fetchMessages(id); // initial load
        chatInterval = setInterval(() => {
            fetchMessages(id);
        }, 3000);
    }

    function fetchMessages(billId) {
        $.ajax({
            url: "{{ route('bill.get-messages') }}",
            type: "POST",
            data: {
                bill_id: billId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $('#chatMessages').html('');

                response.chat.forEach(function(item) {
                    const isCurrentUser = item.user_id == {{ session('user')->id }};
                    const alignmentClass = isCurrentUser ? 'chat-right' : 'chat-left';
                    const senderName = isCurrentUser ? 'You' : item.user_name;
                    const time = item.time ?? item.created_at;

                    $('#chatMessages').append(`
                <div class="chat-message ${alignmentClass}">
                    <div class="message-header">
                        <strong>${senderName}</strong> <small class="text-muted">${time}</small>
                    </div>
                    <div class="message-body">${item.message}</div>
                </div>
            `);
                });

                const chatBox = document.getElementById('chatMessages');
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    }


    function sendMessage() {
        const input = document.getElementById("chatInput");
        const message = input.value.trim();
        const billId = document.getElementById("bill_ids").value;

        if (!message) return;

        $.ajax({
            url: "{{ route('bill.send-message') }}",
            type: "POST",
            data: {
                message: message,
                bill_id: billId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    input.value = '';
                    fetchMessages(billId); // refresh chat
                }
            },
            error: function(xhr) {
                console.error("Error sending message:", xhr.responseText);
            }
        });
    }

    // Stop polling when modal is closed
    $('#groupChatModal').on('hide.bs.modal', function() {
        if (chatInterval) clearInterval(chatInterval);
    });
</script>
