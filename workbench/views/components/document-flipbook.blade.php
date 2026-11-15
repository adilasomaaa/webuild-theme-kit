@props([
    'previewUrl',
    'fileSize',
    'title',
])

<div
    data-document-flipbook
    data-preview-url="{{ $previewUrl }}"
    data-file-size="{{ (int) $fileSize }}"
    data-title="{{ $title }}"
    class="h-[650px] w-full overflow-hidden rounded border border-slate-200 bg-slate-100"
>
    <div class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center" role="status">
        <p class="text-sm font-medium text-slate-700">Pratinjau flipbook sedang dimuat.</p>
        <a href="{{ $previewUrl }}" class="civic-primary-text text-sm font-semibold hover:underline">Buka PDF langsung</a>
    </div>
    <noscript>
        <iframe src="{{ $previewUrl }}" class="h-full w-full border-0" title="{{ $title }}"></iframe>
    </noscript>
</div>
