<script>
    function limitCharacters(textareaId, feedbackId) {
        const textarea = document.getElementById(textareaId);
        const feedback = document.getElementById(feedbackId);

        textarea.addEventListener("input", () => {
            let value = textarea.value;

            // Enforce character limit
            if (value.length > 75) {
                value = value.slice(0, 75);
            }

            textarea.value = value;

            feedback.textContent = `Maximum 75 characters — ${value.length}/75`;
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        limitCharacters("stageDescription", "stageDescriptionFeedback");
        limitCharacters("billDescription", "billDescriptionFeedback");
    });
</script>
