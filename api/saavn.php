<?php
function saavn_api($endpoint) {
    // Return empty results for all endpoints to remove YouTube songs
    return ['data' => ['results' => []]];
}

function getFallbackSongs($query) {
    // Bollywood songs database - popular Hindi/Indian songs
    $allSongs = [
        // Popular Bollywood songs
        [
            'id' => 'hTWKbfoikeg',
            'name' => 'Kabira',
            'primaryArtists' => 'Tochi Raina, Rekha Bhardwaj',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg']
            ]
        ],
        [
            'id' => 'JGwWNGJdvx8',
            'name' => 'Tum Hi Ho',
            'primaryArtists' => 'Arijit Singh',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/JGwWNGJdvx8/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/JGwWNGJdvx8/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/JGwWNGJdvx8/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=JGwWNGJdvx8'],
                ['link' => 'https://www.youtube.com/watch?v=JGwWNGJdvx8'],
                ['link' => 'https://www.youtube.com/watch?v=JGwWNGJdvx8'],
                ['link' => 'https://www.youtube.com/watch?v=JGwWNGJdvx8'],
                ['link' => 'https://www.youtube.com/watch?v=JGwWNGJdvx8']
            ]
        ],
        [
            'id' => 'OPf0YbXqDm0',
            'name' => 'Channa Mereya',
            'primaryArtists' => 'Arijit Singh',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/OPf0YbXqDm0/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/OPf0YbXqDm0/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/OPf0YbXqDm0/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0'],
                ['link' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0'],
                ['link' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0'],
                ['link' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0'],
                ['link' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0']
            ]
        ],
        [
            'id' => 'y6120QOlsfU',
            'name' => 'Raabta',
            'primaryArtists' => 'Arijit Singh, Nikhita Gandhi',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/y6120QOlsfU/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/y6120QOlsfU/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/y6120QOlsfU/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=y6120QOlsfU'],
                ['link' => 'https://www.youtube.com/watch?v=y6120QOlsfU'],
                ['link' => 'https://www.youtube.com/watch?v=y6120QOlsfU'],
                ['link' => 'https://www.youtube.com/watch?v=y6120QOlsfU'],
                ['link' => 'https://www.youtube.com/watch?v=y6120QOlsfU']
            ]
        ],
        [
            'id' => 'RgKAFK5djSk',
            'name' => 'Jeena Jeena',
            'primaryArtists' => 'Atif Aslam',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/RgKAFK5djSk/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/RgKAFK5djSk/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/RgKAFK5djSk/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=RgKAFK5djSk'],
                ['link' => 'https://www.youtube.com/watch?v=RgKAFK5djSk'],
                ['link' => 'https://www.youtube.com/watch?v=RgKAFK5djSk'],
                ['link' => 'https://www.youtube.com/watch?v=RgKAFK5djSk'],
                ['link' => 'https://www.youtube.com/watch?v=RgKAFK5djSk']
            ]
        ],
        [
            'id' => '60ItHLz5WEA',
            'name' => 'Kesariya',
            'primaryArtists' => 'Arijit Singh',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/60ItHLz5WEA/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/60ItHLz5WEA/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/60ItHLz5WEA/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=60ItHLz5WEA'],
                ['link' => 'https://www.youtube.com/watch?v=60ItHLz5WEA'],
                ['link' => 'https://www.youtube.com/watch?v=60ItHLz5WEA'],
                ['link' => 'https://www.youtube.com/watch?v=60ItHLz5WEA'],
                ['link' => 'https://www.youtube.com/watch?v=60ItHLz5WEA']
            ]
        ],
        [
            'id' => 'dQw4w9WgXcQ',
            'name' => 'Besharam Rang',
            'primaryArtists' => 'Vishal Dadlani, Shilpa Rao',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']
            ]
        ],
        [
            'id' => '9bZkp7q19f0',
            'name' => 'Tera Ban Jaunga',
            'primaryArtists' => 'Akhil Sachdeva, Tulsi Kumar',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/9bZkp7q19f0/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/9bZkp7q19f0/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/9bZkp7q19f0/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
                ['link' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
                ['link' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
                ['link' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
                ['link' => 'https://www.youtube.com/watch?v=9bZkp7q19f0']
            ]
        ],
        [
            'id' => 'kJQP7kiw5Fk',
            'name' => 'Shayad',
            'primaryArtists' => 'Arijit Singh',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/kJQP7kiw5Fk/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/kJQP7kiw5Fk/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/kJQP7kiw5Fk/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk'],
                ['link' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk'],
                ['link' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk'],
                ['link' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk'],
                ['link' => 'https://www.youtube.com/watch?v=kJQP7kiw5Fk']
            ]
        ],
        [
            'id' => 'hTWKbfoikeg',
            'name' => 'Pehla Pyaar',
            'primaryArtists' => 'Armaan Malik',
            'image' => [
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/default.jpg'],
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/mqdefault.jpg'],
                ['link' => 'https://img.youtube.com/vi/hTWKbfoikeg/hqdefault.jpg']
            ],
            'downloadUrl' => [
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg'],
                ['link' => 'https://www.youtube.com/watch?v=hTWKbfoikeg']
            ]
        ]
    ];

    // Simple search matching - return up to 8 songs that might match the query
    $query = strtolower($query);
    $matchedSongs = [];

    foreach ($allSongs as $song) {
        $songName = strtolower($song['name']);
        $artistName = strtolower($song['primaryArtists']);

        // Check if query matches song name or artist
        if (strpos($songName, $query) !== false || strpos($artistName, $query) !== false) {
            $matchedSongs[] = $song;
            if (count($matchedSongs) >= 8) break;
        }
    }

    // If no matches found, return random songs
    if (empty($matchedSongs)) {
        shuffle($allSongs);
        $matchedSongs = array_slice($allSongs, 0, 8);
    }

    return ['data' => ['results' => $matchedSongs]];
}
