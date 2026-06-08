<div x-data="yaCaptchaField()">
    <div class="smart-captcha" data-sitekey="{{ config('services.yacaptcha.client') }}" data-callback="onSmartCaptchaPassed"></div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('yaCaptchaField', () => ({
            submitBtn: null,

            init() {
                this.submitBtn = this.$root.closest('form').querySelector('button[type="submit"]');

                if (this.submitBtn) {
                    this.submitBtn.disabled = true;
                    this.submitBtn.style.opacity = '0.5';
                    this.submitBtn.style.cursor = 'not-allowed';
                }

                window.onSmartCaptchaPassed = (token) => {
                    if (this.submitBtn) {
                        this.submitBtn.disabled = false;
                        this.submitBtn.style.opacity = '1';
                        this.submitBtn.style.cursor = 'pointer';
                    }
                };
            }
        }))
    })
</script>
