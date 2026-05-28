@php
    $flashMessages = [];

    // Flash dari session Laravel default (with('success'/'error'))
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if (session()->has($type)) {
            $flashMessages[] = ['type' => $type, 'message' => (string) session($type)];
        }
    }

    // Flash custom dari $flash array yang di-pass controller
    if (isset($flash) && is_array($flash)) {
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (! empty($flash[$type])) {
                $flashMessages[] = ['type' => $type, 'message' => (string) $flash[$type]];
            }
        }
    }
@endphp

@if (! empty($flashMessages))
    <script type="application/json" id="appFlashMessages">
        {!! json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
@endif
