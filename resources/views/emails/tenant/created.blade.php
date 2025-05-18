@component('mail::message')
# Tenant Created

Hello {{ $name }},

Your account has been successfully created. Here are your login details:

- **Username**: {{ $user->email }}
- **Password**: {{ $password }}
- **Subdomain**: {{ $domain }}

Please login at {{ config('app.url') }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
