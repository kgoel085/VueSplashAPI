<?php 
    return [
        'scopes' => [
            'users' => [
                'public' => 'Read public data',
                'read_user' => 'Access user’s private data',
                'write_user' => 'Update the user’s profile.',
                'write_likes' => 'Like or unlike a photo on the user’s behalf.',
                'write_followers' => 'Follow or unfollow a user on the user’s behalf.'
            ],
            'photos' => [
                'read_photos' => 'Read private data from the user’s photos',
                'write_photos' => 'Update photos on the user’s behalf.'
            ],
            'collections' => [
                'read_collections' => 'View a user’s private collections.',
                'write_collections' => 'Create and update a user’s collections'
            ]
        ]
    ]


?>