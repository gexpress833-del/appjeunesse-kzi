@extends('layouts.app')

@section('title', 'Publier des photos')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Publier des photos</h1>

<form method="POST" action="{{ route('gallery.store') }}" enctype="multipart/form-data"
      class="mt-6 max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" id="gallery-upload-form">
    @csrf

    <div>
        <label class="block text-sm font-medium text-slate-700">Photos *</label>
        <p class="mt-1 text-xs text-slate-500">Sélectionnez jusqu’à 20 images. Elles seront toutes publiées pour le même événement.</p>
        <input id="gallery-upload-input" type="file" name="photos[]" multiple accept="image/*"
               class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100" required>
        <div id="gallery-upload-preview" class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4"></div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Titre</label>
        <input name="title" value="{{ old('title') }}" placeholder="Ex. Culte du dimanche"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" rows="4" placeholder="Quelques mots sur la photo…"
                  class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Événement associé *</label>
        <select name="event_id" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">— Sélectionnez un événement —</option>
            @foreach ($events as $event)
                <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>{{ $event->name }}</option>
            @endforeach
        </select>
    </div>

    <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Publier</button>
</form>

<script>
    const input = document.getElementById('gallery-upload-input');
    const preview = document.getElementById('gallery-upload-preview');

    if (input && preview) {
        input.addEventListener('change', function () {
            preview.innerHTML = '';

            const files = Array.from(this.files || []);
            const summary = document.createElement('p');
            summary.className = 'col-span-full text-xs font-semibold text-indigo-600';
            summary.textContent = files.length + ' image(s) sélectionnée(s)';
            preview.appendChild(summary);

            files.forEach((file) => {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    const item = document.createElement('div');
                    item.className = 'overflow-hidden rounded-xl border border-slate-200 bg-slate-50';
                    item.innerHTML = '<img src="' + event.target.result + '" class="h-24 w-full object-cover" alt="Prévisualisation">';
                    preview.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        });
    }
</script>
@endsection
