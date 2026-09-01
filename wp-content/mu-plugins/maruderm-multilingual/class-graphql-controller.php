<?php

namespace Maruderm\Multilingual;

final class GraphqlController
{
    public function __construct(private readonly ProductIdentityResolver $resolver)
    {
    }

    public function register(): void
    {
        add_action('graphql_register_types', [$this, 'registerTypes']);
    }

    public function registerTypes(): void
    {
        if (! function_exists('register_graphql_object_type') || ! function_exists('register_graphql_field')) {
            return;
        }

        register_graphql_object_type('MarudermLocalizedProductIdentity', [
            'fields' => [
                'canonicalDatabaseId' => ['type' => 'Int'],
                'presentationDatabaseId' => ['type' => 'Int'],
                'requestedLanguage' => ['type' => 'String'],
                'resolvedLanguage' => ['type' => 'String'],
                'fallbackUsed' => ['type' => 'Boolean'],
                'canonicalSlug' => ['type' => 'String'],
                'localizedSlug' => ['type' => 'String'],
            ],
        ]);

        register_graphql_field('RootQuery', 'marudermLocalizedProductIdentity', [
            'type' => 'MarudermLocalizedProductIdentity',
            'args' => [
                'slug' => ['type' => ['non_null' => 'String']],
                'language' => ['type' => ['non_null' => 'String']],
            ],
            'resolve' => fn ($root, array $args): ?array => $this->resolver->resolveBySlug(
                (string) $args['slug'],
                (string) $args['language']
            ),
        ]);
    }
}
