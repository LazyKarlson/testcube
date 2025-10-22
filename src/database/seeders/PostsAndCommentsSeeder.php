<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostsAndCommentsSeeder extends Seeder
{
    /**
     * Themed post templates with titles, bodies, and related comment templates
     */
    private array $postThemes = [
        [
            'title' => 'The Future of Artificial Intelligence in Healthcare',
            'body' => 'Artificial intelligence is revolutionizing healthcare in unprecedented ways. From diagnostic imaging to personalized treatment plans, AI algorithms are helping doctors make more accurate decisions faster than ever before. Machine learning models can now detect diseases like cancer in early stages with remarkable precision. Natural language processing is streamlining medical documentation, allowing healthcare professionals to spend more time with patients. Robotic surgery assisted by AI is making complex procedures safer and more efficient. The integration of AI in drug discovery is accelerating the development of new medications. Predictive analytics are helping hospitals manage resources more effectively. Telemedicine platforms powered by AI are making healthcare more accessible to remote communities. As we move forward, the synergy between human expertise and artificial intelligence promises to create a healthcare system that is more accurate, efficient, and patient-centered than ever imagined.',
            'comments' => [
                'This is fascinating! I recently read about AI detecting diabetic retinopathy with 95% accuracy.',
                'Great article! However, we need to address the ethical concerns around patient data privacy.',
                'As a healthcare professional, I can confirm AI is already making a huge difference in our daily work.',
                'The potential is enormous, but we must ensure AI complements rather than replaces human judgment.',
                'I wonder how this will affect healthcare costs in the long run. Will it make treatment more affordable?',
                'The robotic surgery part is incredible. My uncle had AI-assisted heart surgery last year.',
                'We should also consider the training required for medical staff to work with these new technologies.',
                'What about smaller clinics and hospitals? Will they have access to these AI tools?',
            ],
        ],
        [
            'title' => 'Sustainable Living: Small Changes That Make a Big Impact',
            'body' => 'Living sustainably doesn\'t require drastic lifestyle changes. Small, consistent actions can collectively make a significant environmental impact. Start by reducing single-use plastics in your daily routine. Bring reusable bags, bottles, and containers wherever you go. Consider composting kitchen waste to reduce landfill contributions and create nutrient-rich soil for gardening. Switch to energy-efficient LED bulbs and appliances to lower your carbon footprint. Support local farmers and businesses to reduce transportation emissions. Embrace minimalism by buying only what you truly need and choosing quality over quantity. Repair items instead of immediately replacing them. Use public transportation, bike, or walk when possible. Plant native species in your garden to support local ecosystems. These simple changes, when adopted by many, create a ripple effect that benefits our planet for generations to come.',
            'comments' => [
                'I started composting six months ago and it\'s amazing how much waste we\'ve diverted from landfills!',
                'The LED bulb switch saved me about 30% on my electricity bill. Win-win!',
                'Shopping local has also helped me discover amazing small businesses in my community.',
                'I\'ve been using the same reusable water bottle for 5 years now. It\'s the little things!',
                'Great tips! I\'d add that buying second-hand is another excellent way to reduce consumption.',
                'My family started a small vegetable garden. It\'s rewarding and reduces our grocery footprint.',
                'Public transportation isn\'t great in my area, but carpooling has been a good alternative.',
            ],
        ],
        [
            'title' => 'Remote Work Revolution: Productivity Tips for Digital Nomads',
            'body' => 'The shift to remote work has transformed how we approach productivity and work-life balance. Creating a dedicated workspace, even in a small apartment, helps establish boundaries between professional and personal life. Invest in ergonomic furniture to prevent physical strain during long working hours. Establish a consistent routine that includes regular breaks and physical activity. Use productivity tools like time-blocking and the Pomodoro Technique to maintain focus. Communication is crucial in remote settings, so over-communicate with your team and set clear expectations. Take advantage of flexibility by working during your peak productivity hours. Minimize distractions by using website blockers and keeping your phone in another room during deep work sessions. Join virtual coworking sessions or local coworking spaces to combat isolation. Remember that productivity isn\'t about working more hours, but about working smarter and maintaining sustainable habits that support both your career and well-being.',
            'comments' => [
                'The Pomodoro Technique changed my life! 25 minutes of focus, 5 minutes break - perfect rhythm.',
                'I struggled with remote work until I created a separate office space. Game changer!',
                'Virtual coworking has been amazing for accountability. Highly recommend trying it.',
                'As someone who\'s been remote for 3 years, I can\'t stress enough the importance of routine.',
                'Don\'t forget to actually log off at the end of the day. Burnout is real in remote work.',
                'I use noise-cancelling headphones and lo-fi music to create my focus zone.',
                'The flexibility is great, but you need discipline. It\'s not for everyone.',
                'My productivity skyrocketed when I started working during my natural peak hours (6-10 AM).',
            ],
        ],
        [
            'title' => 'The Art of Mindful Eating: Transform Your Relationship with Food',
            'body' => 'Mindful eating is about developing a healthy, conscious relationship with food that goes beyond diets and restrictions. It starts with paying attention to hunger and fullness cues rather than eating by the clock or out of habit. Slow down during meals, chewing thoroughly and savoring each bite. Put away distractions like phones and television to fully experience your food. Notice the colors, textures, aromas, and flavors of what you\'re eating. Listen to your body\'s signals and stop eating when you\'re satisfied, not stuffed. Avoid labeling foods as "good" or "bad" and instead focus on how different foods make you feel. Practice gratitude for your meals and the effort that went into producing them. Mindful eating isn\'t about perfection but about bringing awareness and intention to one of life\'s most fundamental activities. This approach can lead to better digestion, more enjoyment of food, and a healthier relationship with eating overall.',
            'comments' => [
                'This approach helped me overcome years of yo-yo dieting. It\'s liberating!',
                'I never realized how fast I was eating until I started practicing mindfulness.',
                'The "no distractions" rule was hard at first, but now meals are my favorite part of the day.',
                'As a nutritionist, I always recommend mindful eating to my clients. It works!',
                'I\'ve lost weight without even trying, just by listening to my body\'s signals.',
                'The gratitude practice has made me more conscious about food waste too.',
                'This is especially important to teach children. Building healthy habits early matters.',
            ],
        ],
        [
            'title' => 'Cybersecurity Essentials: Protecting Your Digital Life in 2024',
            'body' => 'In our increasingly connected world, cybersecurity is no longer optional but essential for everyone. Start with strong, unique passwords for each account, using a password manager to keep track of them all. Enable two-factor authentication wherever possible to add an extra layer of security. Be skeptical of unsolicited emails, messages, and phone calls asking for personal information. Keep your software, operating systems, and apps updated to patch security vulnerabilities. Use a VPN when connecting to public Wi-Fi networks to encrypt your data. Regularly backup important files to both cloud storage and physical drives. Review privacy settings on social media platforms and limit what information you share publicly. Be cautious about what you click on and download. Monitor your financial accounts regularly for suspicious activity. Educate yourself about common scams like phishing and social engineering. Remember, cybersecurity is an ongoing practice, not a one-time setup. Stay informed about emerging threats and adapt your security measures accordingly.',
            'comments' => [
                'I use Bitwarden as my password manager and it\'s been fantastic. Highly recommend!',
                'Got phished last year and learned this lesson the hard way. Don\'t be like me!',
                'Two-factor authentication saved my account when someone tried to hack it.',
                'The VPN tip is crucial. I never connect to public WiFi without one anymore.',
                'Regular backups saved me when ransomware hit my computer. Can\'t stress this enough!',
                'I wish schools taught this stuff. So many people are vulnerable online.',
                'What about hardware security keys? Are they worth the investment?',
                'Great article! I\'d add: be careful about what you post on social media.',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating posts and comments...');

        // Get all author users (excluding viewers)
        $authors = User::whereHas('roles', function ($query) {
            $query->where('name', 'author');
        })->get();

        // Get all users except viewers for commenting
        $commenters = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'editor', 'author']);
        })->get();

        if ($authors->isEmpty()) {
            $this->command->error('No author users found! Please run UsersSeeder first.');
            return;
        }

        if ($commenters->isEmpty()) {
            $this->command->error('No users found for commenting! Please run UsersSeeder first.');
            return;
        }

        $totalPosts = 0;
        $totalComments = 0;
        $usedTitles = []; // Track used titles to ensure uniqueness

        // Create posts for each author
        foreach ($authors as $author) {
            $postsCount = rand(1, 10);

            for ($i = 0; $i < $postsCount; $i++) {
                // Select a random theme
                $theme = $this->postThemes[array_rand($this->postThemes)];

                // Create unique title with variations
                $titleVariations = [
                    '',
                    'Exploring ',
                    'Understanding ',
                    'A Deep Dive into ',
                    'The Complete Guide to ',
                    'Mastering ',
                    'An Introduction to ',
                    'Advanced Techniques in ',
                    'The Ultimate Guide to ',
                    'A Beginner\'s Guide to ',
                    'Professional Insights on ',
                    'The Essential Guide to ',
                    'Rethinking ',
                    'Modern Approaches to ',
                    'The Science Behind ',
                ];

                // Try to create a unique title
                $title = '';
                $attempts = 0;
                do {
                    $prefix = $titleVariations[array_rand($titleVariations)];
                    $suffix = $attempts > 0 ? ' - Part ' . ($attempts + 1) : '';
                    $title = $prefix . $theme['title'] . $suffix;
                    $attempts++;
                } while (in_array($title, $usedTitles) && $attempts < 20);

                // If still not unique after 20 attempts, add timestamp
                if (in_array($title, $usedTitles)) {
                    $title .= ' (' . now()->timestamp . mt_rand(1000, 9999) . ')';
                }

                $usedTitles[] = $title;

                // Randomly decide if published or draft (80% published)
                $isPublished = rand(1, 100) <= 80;
                
                $post = Post::create([
                    'author_id' => $author->id,
                    'title' => $title,
                    'body' => $theme['body'],
                    'status' => $isPublished ? 'published' : 'draft',
                    'published_at' => $isPublished ? now()->subDays(rand(1, 365)) : null,
                    'created_at' => now()->subDays(rand(1, 365)),
                ]);

                $totalPosts++;

                // Create 5 to 50 comments for this post
                $commentsCount = rand(5, 50);
                $availableComments = $theme['comments'];
                
                for ($j = 0; $j < $commentsCount; $j++) {
                    // Select a random commenter (can be same user multiple times)
                    $commenter = $commenters->random();
                    
                    // Select a comment from the theme or generate a generic one
                    if (!empty($availableComments) && rand(1, 100) <= 70) {
                        // 70% chance to use themed comment
                        $commentBody = $availableComments[array_rand($availableComments)];
                    } else {
                        // 30% chance to use generic comment
                        $genericComments = [
                            'Thanks for sharing this insightful post!',
                            'I completely agree with your perspective on this.',
                            'This is exactly what I needed to read today.',
                            'Great points! I\'ll definitely try implementing these ideas.',
                            'Interesting take. I hadn\'t considered it from this angle before.',
                            'Bookmarking this for future reference. Very helpful!',
                            'Could you elaborate more on this topic?',
                            'I have a different opinion, but I respect your viewpoint.',
                            'This resonates with my own experience. Well written!',
                            'Looking forward to more content like this.',
                        ];
                        $commentBody = $genericComments[array_rand($genericComments)];
                    }

                    Comment::create([
                        'author_id' => $commenter->id,
                        'post_id' => $post->id,
                        'body' => $commentBody,
                        'created_at' => $post->created_at->addMinutes(rand(10, 10000)),
                    ]);

                    $totalComments++;
                }
            }
        }

        $this->command->info('');
        $this->command->info('✅ Posts and comments created successfully!');
        $this->command->info('');
        $this->command->info("Summary:");
        $this->command->info("- Total posts created: {$totalPosts}");
        $this->command->info("- Total comments created: {$totalComments}");
        $this->command->info("- Average comments per post: " . round($totalComments / $totalPosts, 1));
    }
}

