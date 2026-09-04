{{-- Messages de session et erreurs de validation --}}
@if (session('success'))
    <div class="flash ok" role="status"><i class="ri-checkbox-circle-line"></i><div>{{ session('success') }}</div><button class="close" type="button" aria-label="Fermer"><i class="ri-close-line"></i></button></div>
@endif
@if (session('error'))
    <div class="flash err" role="alert"><i class="ri-error-warning-line"></i><div>{{ session('error') }}</div><button class="close" type="button" aria-label="Fermer"><i class="ri-close-line"></i></button></div>
@endif
@if ($errors->any())
    <div class="flash err" role="alert"><i class="ri-error-warning-line"></i>
        <div>
            @if ($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
        </div>
        <button class="close" type="button" aria-label="Fermer"><i class="ri-close-line"></i></button>
    </div>
@endif
