<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Questionnaire;
use App\Models\User;
use App\Services\StoreRatingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaireData = [
            [
                'type' => 'Authorized Products',
                'category' => 'Authorized Products, Produced and Sold',
                'subcategory' => null,
                'questions' => [
                    'Authorized Products Produced',
                    'Authorized Products Sold',
                    'Authorized Non- Bread Products Sold',
                ],
            ],
            [
                'type' => 'Cleanliness, Sanitation and Maintenance',
                'category' => 'Selling Area, Counter Area and Snack Area',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Main signage, Directional Signage. Standard, Updated, Clean, Not faded.',
                    'Sunshade (If applicable only). Hanged properly and clean.',
                    'Display Racks and Siopao Warmer. Clean and in good condition.',
                    'Selling implements (price tags, tongs, other related items). Displayed, clean, complete, no handwritten, not faded.',
                    'Tables and Chairs. Regularly Cleaned and in good condition.',
                    'Frying Station (For with Frying Station Only). Clean, not greasy and in good condition.',
                    'Cashiers Table. Clean and organized, no items not related to cashiering.',
                    'Ad Board/Posters/Marketing Collaterals/Promo (DM request). Properly placed, Updated materials are posted.',
                    'Bread Crates (Blue bread Crates only since yellow and red comes from commissary). Clean and piling limits observed, Products are covered and well-arranged.',
                    'Refrigerator, Chiller,Dispenser and Freezer. Clean and in good condition (with no personal items).',
                    'Free from pest',
                    'Pull-out Bread Container & Pull out products. Clean, Good condition, Pull out products inside grouped & Labelled with no variance.',
                ],
            ],
            [
                'type' => 'Cleanliness, Sanitation and Maintenance',
                'category' => 'Production Area',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Equipment and Fixture, Tools, Utensils and Molders. Clean and in good condition especially oven gauge.',
                    'Weighing Scale. Functional, Calibrated an in good condition.',
                    'Tools, Utensils and Molders. *Clean and sufficient.',
                    'Containers. Clean and with cover.',
                    'Gas Tank and Gas storage. Clean and with extra full cylinder(If no Gauge not a demerit but included in the remarks).',
                    'Free from pest',
                    'Grease Trap (except outlet). Regularly cleaned and clean.',
                ],
            ],
            [
                'type' => 'Cleanliness, Sanitation and Maintenance',
                'category' => 'Scaling Room and Stockroom',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Ingredients Containers (5-25 kgs like flour and the like). Clean with cover and label.',
                    'Tools and utensils (ladle, canister and the like below 5 kilos). Clean and adequate.',
                    'Weighing Scale. Functional and calibrated.',
                    'Free from pest',
                    'Fixtures (Cabinet, Scaling Table, pallets and the like). Clean and in good condition.',
                ],
            ],
            [
                'type' => 'Cleanliness, Sanitation and Maintenance',
                'category' => 'Common in All Areas / Others',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Ceilings and walls. Clean and standard paint color (painting for verification to DCM).',
                    'Door (Production Area, Scaling Area and Selling Area). Clean and with lock.',
                    'Floor. In good in condition and clean.',
                    'Lighting, Ceiling Fans, Exhaust Fan (Scaling, Production and Selling). Clean and functional.',
                    'Wall Clock (Scaling, Production and Selling). Clean and functional.',
                    'Waste Receptacles (Standard-Bio and Non-Bio allowed inside). Covered and with liner, Segregated and disposed regularly.',
                    'Roll -up Shutter Guide. *Clean and well-kept.',
                    'Sidewalk/Back Area/Surroundings. Clean and no obstructions.',
                    'Glass Panels. Regularly cleaned and clean.',
                    'Pantry (Production Area) (For with pantry only or Put "N/A" applicable). *Cleaned and organized.',
                    'Lockers. Clean and with lock.',
                    'Bakery/Safety Signages (Fire Exit, Clean as you go and the like). Clean, updated and not faded.',
                    'Sink. Clean with towel and liquid hand soap (strictly no bar soap).',
                    'Slop Sink and Drainage (only for those with slop sink only if not "N/A"). Clean and in good condition.',
                    'Comfort Room. Clean and door with hook to hang apron/clothes (outside CR area).',
                    'Storage area (Non Bread, empty sacks, containers and the like). Clean and organized.',
                    'Medicine Cabinet/Kit. Clean and with available medicines.',
                    'Grooming and Hygiene (All JBS Personnel). Complete uniform and with ID/Nameplate (Scaler and Baker may not wear the ID during production but they have to present during inspection)',
                ],
            ],
            [
                'type' => 'Cleanliness, Sanitation and Maintenance',
                'category' => 'Common in All Areas / Others',
                'subcategory' => 'Store Conditions',
                'questions' => [
                    'No need Minor repairs (Door Knobs, Sink, Wall Mural, Menu Board replacement, chairs and tables partially damaged)',
                    'No need Major repairs (Damage Doors, Repainting of Ceiling and Walls, partially or Totally damaged tiles)',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Premium Quality Products',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Product Quality Profile. Quality products produced based on standard profile.',
                    'Scaled Ingredients. Not expired, sealed and labelled (2 days max in the ingredients cabinet).',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Production Schedule',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Production Schedule. Production schedule updated and followed (Products listed in the production schedule are produced by the bakeshops and filled out in the Production Report).',
                    'Hourly hot bread available and announced by baker.',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Tools and molders (Cutter not allowed, paint brush and molder for replacement if any). Standard tools and molder used.',
                    'Mixing. Standard product mixing procedure followed.',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Make-up (shaping and sizing this is where counting is done, weighing of dough at certain intervals)',
                'questions' => [
                    'Standard make-up steps followed',
                    'Standard preparation of toppings/filing followed',
                    'Dough seam properly sealed',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Proofing/Resting/Fermentation',
                'questions' => [
                    'Resting or fermentation observed (30 mins, 1 hour and 2 hours-Dough)',
                    'Products properly proofed (Patubo)',
                    'Dough covered with plastic',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Baking',
                'questions' => [
                    'Baking temperature (gauge) and baking time followed',
                    'Proper baking method followed',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Frying',
                'questions' => [
                    'Fresh oil used or used up to allowable recycling (Refill only)',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Cooling',
                'questions' => [
                    'Proper cooling time observed (5-10 mins for bun types and 1-2 hours for sliced bread if any)',
                ],
            ],
            [
                'type' => 'Production Quality',
                'category' => 'Bread Production',
                'subcategory' => 'Accurate Ingredients Solutions/Preparation',
                'questions' => [
                    'Color and flavor solution',
                    'Breading finishing',
                    'Conditions raisins (if commissary "n/a")',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Assessment Areas',
                'questions' => [
                    'Active and Authentic Permits and Certificates. Complete / posted. Bakeshop personnel health card and vaccine card available.',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Implementation of Operational Practices',
                'questions' => [
                    'Calculator (Selling, Scaler, Production)',
                    'Logbooks',
                    'Process Guide and Scaling Recipe Book available',
                    'Bulletin Board',
                    'Cash Control System/Vault',
                    'Equipment maintenance schedule',
                    'On-time submission of bakery reports',
                    'Promo Implementation. Properly executed with complete collaterals.',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Productions',
                'questions' => [
                    'No. of kilos properly recorded',
                    'Toppings, dusting and fillings recorded',
                    'All products recorded in the production report',
                    'Recorded standard yield and grams per products (Pass Through filled out forms from cashier)',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Selling',
                'questions' => [
                    'Prices of product displayed',
                    'Products displayed listed in the JBMIS',
                    'Cash count conducted every end of shift',
                    'Swas, Discount and other deliveries properly recorded (if applicable)',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Bakery Documents',
                'questions' => [
                    'Reports accurately accomplished by personnel in charge',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Safety and Security Measures',
                'questions' => [
                    'Fire extinguisher not expired and mounted (Hang)',
                    'Charge emergency lights',
                    'Ready generator set',
                    'Working CCTV',
                    'Scaling room lock if scaler is out',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Operational Requirements',
                'subcategory' => 'Delivery Units (If with delivery units only)',
                'questions' => [
                    'Clean and in good condition',
                    'Products with price tags and products properly arranged',
                ],
            ],
            [
                'type' => 'Operational Excellence and Food Safety',
                'category' => 'Standards on Food Safety',
                'subcategory' => 'Assessment Areas - Food Safety',
                'questions' => [
                    'Ingredients received are free from damage (rat bites, not expired)',
                    'Regular application of pest control (branch and junior)',
                    'Proper labeling of cleaning and pest control tools',
                    'Proper use of hand gloves and proper hand washing',
                    'Off the floor policy',
                    'Use of proper cleaning agents',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Customer Service',
                'subcategory' => 'Customer Care',
                'questions' => [
                    'Greet',
                    'Serve without delay',
                    'Offer add-ons',
                    'Compute the bill',
                    'Thank you and invite',
                    'Serve with a smile',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Customer Service',
                'subcategory' => 'Service Quality and efficiency',
                'questions' => [
                    'Eye contact',
                    'Speaks clearly and knowledgeable on the JBS Products',
                    'Loose coins and bills ready',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Customer Service',
                'subcategory' => 'Service',
                'questions' => [
                    "Use Standard Julie's Codes",
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Product Merchandise',
                'subcategory' => "Julie's Product Merchandising and Planogram",
                'questions' => [
                    'Showcase regularly filled',
                    'Standard Planogram followed',
                    'Pull out time and shelf life observed',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Product Merchandise',
                'subcategory' => 'Piling Limits',
                'questions' => [
                    'Standard piling limit for each bread type followed',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Product Merchandise',
                'subcategory' => 'Non-Bread Products',
                'questions' => [
                    'Stocks are available and not expired',
                    'Stocks properly kept and organized with price tags/list',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Product Merchandise',
                'subcategory' => 'Table Top/Wall Mounted Non Bread showcase',
                'questions' => [
                    'Standard Planogram followed',
                ],
            ],
            [
                'type' => 'Customer Experience',
                'category' => 'Product Merchandise',
                'subcategory' => 'Showcase Appearance and Maintenance',
                'questions' => [
                    'Not broken, clean, not busted light and the like (3 lights warm white).',
                ],
            ],
        ];

        DB::transaction(function () use ($questionnaireData) {
            $orderTrack = []; // To track order per group

            foreach ($questionnaireData as $data) {
                // Find or create category, but allow null category
                $category = $data['category']
                    ? Category::firstOrCreate(
                        ['name' => $data['category'], 'type' => 'questionnaire'],
                        ['parent_id' => null]
                    )
                    : null;

                // Find or create subcategory, but allow null subcategory
                $subcategory = ($data['subcategory'] && $category)
                    ? Category::firstOrCreate(
                        ['name' => $data['subcategory'], 'type' => 'questionnaire'],
                        ['parent_id' => $category->id]
                    )
                    : null;

                // Define the key to track order per type/category/subcategory
                $groupKey = $data['type'].'-'.($category?->id ?? 'null').'-'.($subcategory?->id ?? 'null');

                // Ensure the order count starts at 1 if it’s the first entry for this group
                if (! isset($orderTrack[$groupKey])) {
                    $orderTrack[$groupKey] = 1;
                }

                // Insert Questions
                foreach ($data['questions'] as $question) {
                    Questionnaire::create([
                        'question' => $question,
                        'order' => $orderTrack[$groupKey]++, // Increment order only within its group
                        'type' => $data['type'],
                        'category_id' => $category?->id,
                        'subcategory_id' => $subcategory?->id,
                    ]);
                }
            }
        });

        // Optional: Seed store ratings
        // DB::transaction(function () {
        //     $createdBy = User::where('email', 'admin1@jfm.test')->first();
        //     $storeRatingService = new StoreRatingService;
        //     $storeRatingService->generateStoreRatingQuestionnaire(1, $createdBy);
        // });
    }
}
