@extends('layouts.app')

@section('scripts')
<script>
    // alert('This is an alert from the abouts page.');
</script>
@endsection

@section('content')
    <section>
        <h2>About Us</h2>
        <p>This is a simple HTML an CSS template to start your project.</p>

        <p>Name: {{ $name }}</p>
        <p>Id: {{ $id }}</p>
    </section>
@endsection


