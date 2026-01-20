{{-- Component untuk Language Switcher dengan localStorage --}}
@props(['short' => false, 'selectClass' => ''])

<div x-data="localeSwitcher()" {{ $attributes->except(['short', 'selectClass']) }}>
    <select 
        x-model="currentLocale"
        @change="switchLocale($event.target.value)"
        class="{{ $selectClass ?: 'text-sm border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500' }}"
    >
        <option value="id">{{ $short ? '🇮🇩 ID' : '🇮🇩 Bahasa Indonesia' }}</option>
        <option value="en">{{ $short ? '🇬🇧 EN' : '🇬🇧 English' }}</option>
    </select>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('localeSwitcher', () => ({
        currentLocale: '{{ app()->getLocale() }}',
        
        init() {
            const savedLocale = localStorage.getItem('locale');
            if (savedLocale && savedLocale !== this.currentLocale) {
                this.currentLocale = savedLocale;
                this.setCookie('locale', savedLocale, 365);
            } else if (!savedLocale) {
                localStorage.setItem('locale', this.currentLocale);
                this.setCookie('locale', this.currentLocale, 365);
            }
        },
        
        async switchLocale(locale) {
            if (locale === this.currentLocale && localStorage.getItem('locale') === locale) {
                return;
            }
            
            try {
                localStorage.setItem('locale', locale);
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    this.setCookie('locale', locale, 365);
                    window.location.reload();
                    return;
                }
                
                const response = await fetch('{{ route("locale.switch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ locale: locale })
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    this.setCookie('locale', locale, 365);
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error switching locale:', error);
                this.setCookie('locale', locale, 365);
                window.location.reload();
            }
        },
        
        setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/;SameSite=Lax';
        }
    }));
});
</script>
@endpush
@endonce