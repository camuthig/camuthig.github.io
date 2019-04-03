@extends('_layouts.master')

@push('meta')
    <meta property="og:title" content="About {{ $page->siteName }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:description" content="A little bit about {{ $page->siteName }}" />
@endpush

@section('body')
    <h1>About</h1>

    <img src="/assets/img/profile.jpeg"
        alt="About image"
        class="flex rounded-full h-64 w-64 bg-contain mx-auto md:float-right my-6 md:ml-10">

    <p class="mb-6">
        My name is Chris Muthig, and I am a software developer with a passion for helping others get more out of technology.
        I have had the opportunity to work in a number of different domains, and I'm always looking for new tools I can help build
        to solve problems people face every day.
    </p>
    <p class="mb-6">
        I have worked in a number of different languages over the years and believe that programming languages are just a tool to solve complex problems.
        You'll often see me experimenting with new languages and trying out new development paradigms to see if they can better
        solve certain problems I may come across.
    </p>
@endsection
