@foreach ($imagenes as $img)
    <p>{{ $img->getClientOriginalName() }}</p>
    <img src="data:{{ $img->getMimeType() }};base64,{{ base64_encode(file_get_contents($img->getRealPath())) }}" width="200" />
@endforeach
