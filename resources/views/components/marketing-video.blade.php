@props([
    'src',
    'badge' => null,
    'aspect' => 'aspect-[9/16]',
    'autoplay' => true,
    'muted' => true,
    'loop' => true,
])

<div
    {{ $attributes->class('marketing-video-root') }}
    data-marketing-video-root
    data-video-autoplay="{{ $autoplay ? 'true' : 'false' }}"
    data-label-play="{{ __('home.video_play') }}"
    data-label-pause="{{ __('home.video_pause') }}"
    data-label-mute="{{ __('home.video_mute') }}"
    data-label-unmute="{{ __('home.video_unmute') }}"
    data-label-fullscreen-enter="{{ __('home.video_fullscreen_enter') }}"
    data-label-fullscreen-exit="{{ __('home.video_fullscreen_exit') }}"
>
    <div class="marketing-video-shell">
        <div class="marketing-video-glow" aria-hidden="true"></div>
        <div class="marketing-video-device">
            <div class="marketing-video-device-top" aria-hidden="true"></div>
            <div class="marketing-video-screen {{ $aspect }}" data-marketing-video-stage>
                <video
                    class="marketing-video-media"
                    playsinline
                    preload="metadata"
                    @if ($autoplay) autoplay @endif
                    @if ($muted) muted @endif
                    @if ($loop) loop @endif
                    data-marketing-video
                >
                    <source src="{{ $src }}" type="video/mp4">
                    {{ __('home.video_fallback') }}
                </video>

                <div class="marketing-video-sheen" aria-hidden="true"></div>

                @if (filled($badge))
                    <div class="marketing-video-badge-wrap">
                        <span class="marketing-video-badge">{{ $badge }}</span>
                    </div>
                @endif

                <div class="marketing-video-controls">
                    <button
                        type="button"
                        class="marketing-video-control"
                        data-video-toggle-fullscreen
                        aria-label="{{ __('home.video_fullscreen_enter') }}"
                        title="{{ __('home.video_fullscreen_enter') }}"
                    >
                        <svg class="marketing-video-icon marketing-video-icon--fullscreen-enter" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M5.75 4A1.75 1.75 0 0 0 4 5.75v3.5a.75.75 0 0 0 1.5 0v-3.5c0-.14.11-.25.25-.25h3.5a.75.75 0 0 0 0-1.5h-3.5Zm9 0a.75.75 0 0 0 0 1.5h3.5c.14 0 .25.11.25.25v3.5a.75.75 0 0 0 1.5 0v-3.5A1.75 1.75 0 0 0 18.25 4h-3.5ZM4.75 14a.75.75 0 0 0-.75.75v3.5C4 19.22 4.78 20 5.75 20h3.5a.75.75 0 0 0 0-1.5h-3.5a.25.25 0 0 1-.25-.25v-3.5a.75.75 0 0 0-.75-.75Zm14 0a.75.75 0 0 0-.75.75v3.5a.25.25 0 0 1-.25.25h-3.5a.75.75 0 0 0 0 1.5h3.5c.97 0 1.75-.78 1.75-1.75v-3.5a.75.75 0 0 0-.75-.75Z" />
                        </svg>
                        <svg class="marketing-video-icon marketing-video-icon--fullscreen-exit" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.72 4.47a.75.75 0 0 1 .53.91l-.9 3.37 3.37-.9a.75.75 0 1 1 .39 1.45l-4.7 1.26a.75.75 0 0 1-.91-.91l1.26-4.7a.75.75 0 0 1 .96-.48Zm6.56 0a.75.75 0 0 1 .96.48l1.26 4.7a.75.75 0 0 1-.91.91l-4.7-1.26a.75.75 0 0 1 .39-1.45l3.37.9-.9-3.37a.75.75 0 0 1 .53-.91ZM7 13.44a.75.75 0 0 1 .91.53l.9 3.37 3.37-.9a.75.75 0 1 1 .39 1.45l-4.7 1.26a.75.75 0 0 1-.91-.91l1.26-4.7a.75.75 0 0 1 .78-.57Zm10 0a.75.75 0 0 1 .78.57l1.26 4.7a.75.75 0 0 1-.91.91l-4.7-1.26a.75.75 0 1 1 .39-1.45l3.37.9-.9-3.37a.75.75 0 0 1 .53-.91Z" />
                        </svg>
                        <span class="sr-only" data-video-toggle-fullscreen-label>{{ __('home.video_fullscreen_enter') }}</span>
                    </button>

                    <button
                        type="button"
                        class="marketing-video-control"
                        data-video-toggle-play
                        aria-label="{{ __('home.video_pause') }}"
                        title="{{ __('home.video_pause') }}"
                    >
                        <svg class="marketing-video-icon marketing-video-icon--play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.5 6.5a1 1 0 0 1 1.53-.85l8 5.5a1 1 0 0 1 0 1.7l-8 5.5A1 1 0 0 1 8.5 17.5v-11Z" />
                        </svg>
                        <svg class="marketing-video-icon marketing-video-icon--pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M8.75 5A1.75 1.75 0 0 0 7 6.75v10.5C7 18.22 7.78 19 8.75 19h.5A1.75 1.75 0 0 0 11 17.25V6.75C11 5.78 10.22 5 9.25 5h-.5Zm6 0A1.75 1.75 0 0 0 13 6.75v10.5c0 .97.78 1.75 1.75 1.75h.5A1.75 1.75 0 0 0 17 17.25V6.75C17 5.78 16.22 5 15.25 5h-.5Z" />
                        </svg>
                        <span class="sr-only" data-video-toggle-play-label>{{ __('home.video_pause') }}</span>
                    </button>

                    <button
                        type="button"
                        class="marketing-video-control"
                        data-video-toggle-mute
                        aria-label="{{ __('home.video_unmute') }}"
                        title="{{ __('home.video_unmute') }}"
                    >
                        <svg class="marketing-video-icon marketing-video-icon--mute" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M11.596 3.644a.75.75 0 0 1 1.154.632v11.448a.75.75 0 0 1-1.154.632L8.353 14.33a.75.75 0 0 0-.398-.114H6.5A2.5 2.5 0 0 1 4 11.716V8.284a2.5 2.5 0 0 1 2.5-2.5h1.455a.75.75 0 0 0 .398-.114l3.243-2.026Z" />
                            <path d="M15.53 7.97a.75.75 0 0 1 1.06 0L18 9.379l1.409-1.408a.75.75 0 1 1 1.06 1.06L19.061 10.44l1.408 1.409a.75.75 0 0 1-1.06 1.06L18 11.5l-1.409 1.409a.75.75 0 0 1-1.06-1.06l1.408-1.409L15.53 9.03a.75.75 0 0 1 0-1.06Z" />
                        </svg>
                        <svg class="marketing-video-icon marketing-video-icon--unmute" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M14.88 4.3a1 1 0 0 1 1.62.78v13.84a1 1 0 0 1-1.62.78l-4.76-3.8H7A3 3 0 0 1 4 12.9v-1.8a3 3 0 0 1 3-3h3.12l4.76-3.8Z" />
                            <path d="M18.25 9a.75.75 0 0 1 1.06 0A5.47 5.47 0 0 1 21 13a5.47 5.47 0 0 1-1.69 4 .75.75 0 1 1-1.06-1.06A3.97 3.97 0 0 0 19.5 13a3.97 3.97 0 0 0-1.25-2.94.75.75 0 0 1 0-1.06Z" />
                            <path d="M16.13 11.12a.75.75 0 0 1 1.06 0c.5.5.81 1.2.81 1.95s-.31 1.45-.81 1.95a.75.75 0 1 1-1.06-1.06c.23-.23.37-.55.37-.89s-.14-.66-.37-.89a.75.75 0 0 1 0-1.06Z" />
                        </svg>
                        <span class="sr-only" data-video-toggle-mute-label>{{ __('home.video_unmute') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
