{{-- Dialogue natif. <x-modal id="x" title="…" size="lg|sm"> … <x-slot:footer>…</x-slot:footer> </x-modal>
     Avec form="…" (attributs du <form>) le contenu est enveloppé dans un formulaire avec @csrf. --}}
@props(['id', 'title', 'size' => '', 'form' => null, 'method' => 'post', 'action' => '', 'upload' => false, 'multipart' => false, 'footer' => null])
<dialog class="modal {{ $size }}" id="{{ $id }}" aria-labelledby="{{ $id }}-title">
    <div class="box">
        @if ($form !== null)
            <form method="post" action="{{ $action }}" @if ($multipart || $upload) enctype="multipart/form-data" @endif @if ($upload) data-upload @endif {!! $form !!}>
                @csrf
                @if (strtolower($method) !== 'post') @method($method) @endif
        @endif
        <div class="modal-head">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="btn btn-ghost btn-icon" data-close aria-label="Fermer"><i class="ri-close-line"></i></button>
        </div>
        <div class="modal-body">{{ $slot }}</div>
        @if ($footer)
            <div class="modal-foot">{{ $footer }}</div>
        @endif
        @if ($form !== null)
            </form>
        @endif
    </div>
</dialog>
