-- ====================================================================
-- SunHelm CHIM AI Bridge: Oghma Infinium Medical Knowledge Pack
-- Provides Two-Tier Knowledge (Commoner Folklore vs Expert Medicine)
-- ====================================================================

BEGIN;

INSERT INTO public.oghma (
    topic,
    aliases,
    knowledge_class_basic,
    topic_desc_basic,
    knowledge_class,
    topic_desc,
    tags,
    category,
    source_type,
    updated_at
)
VALUES
-- 1. ATAXIA
(
    'ataxia',
    'ataxia, acute ataxia, severe ataxia, the dropped hand, thiefs ruin, thiefs palsy, slaughterfish sickness',
    'common',
    'The Dropped Hand, or Thief''s Ruin. Slaughterfish give it to you when they nip your legs in rivers, or you can catch it from skeevers. It turns your fingers clumsy and your arm hangs loose like dead rope from your shoulder. You cannot pick a lock, count coins, or draw a dagger quickly without fumbling. Most folk chew on marsh roots or pray at a shrine to Talos to wake their fingers up.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known among commoners as Ataxia or the Dropped Hand, this affliction is a spinal motor neuropathy cataloged in ancient Cyrodilic treatises. Transmitted through the venomous fin-barbs of slaughterfish or infected skeever bites. The toxin deadens proprioception—the body''s subconscious awareness of its own limbs—causing deadened reflexes, motor ataxia (-15% to -50% fine manipulation), and mottled skin pallor. In late stages, the arm hangs limp in flaccid paresis. Alchemists neutralize the motor block using compounds of Hawk Feathers and Mudcrab Chitin, or divine restoration at a shrine of Kynareth.',
    'disease, ataxia, motor, slaughterfish, skyrim, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 2. BONE BREAK FEVER
(
    'bone_break_fever',
    'bone break fever, breakbone fever, acute bone break fever, severe bone break fever, the bears grasp, bone snap, breakbone',
    'common',
    'Breakbone Fever, also called the Bear''s Grasp. You catch it when a wild bear claws you in the pines or from rusty bear traps in the dirt. It sets your blood on fire and turns your marrow to burning coals. Every rib, shoulder, and leg bone aches so violently you would swear they were snapping in half right under your flesh. Sufferers break out in burning red rashes, shiver constantly, and can barely stand from sheer exhaustion.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Commonly called Bone Break Fever, apothecaries and Imperial scholars document this contagion as Breakbone Fever. Transmitted through apex predator saliva (bears) and rusted trap punctures. The infection attacks the periosteum—the living membrane encasing skeletal bone—generating intense inflammatory pressure that mimics acute fractures without actual breakage. Progresses from mild fever to acute exhaustion (-50 stamina) and severe collapse (-100 stamina), accompanied by bright erythematous petechial rash. Neutralized by alchemical decoctions rich in tannin and chitin—specifically Hawk Feathers compounded with Mudcrab Chitin or Vampire Dust—or divine purification at a shrine of Kynareth.',
    'disease, bone break fever, breakbone, bear, stamina, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 3. ROCKJOINT
(
    'rockjoint',
    'rockjoint, tetanus, acute rockjoint, severe rockjoint, stone joint, lockjaw, the stiffening, wolf joint',
    'common',
    'Stone-Joint. Wolf bites carry it through the wilderness. Your elbows and knees swell up hard as river stone and freeze crooked. Every time you try to swing an axe or draw a sword, your joints grind and lock up tight. Dark purple veins swell across the arms when the stone-rot takes hold. Common folk say you must rub garlic grease on the joints and pray at a shrine to Talos before your arms turn entirely to rock.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'While known to the populace as Rockjoint, trained healers identify this acute affliction as Tetanus. Transmitted via deep animal bite punctures from wolves and bears. The anaerobic contagion corrupts synovial joint fluids, causing rapid calcification and locking limbs in rigid flexion contractures. This degrades weapon leverage and martial skill by up to 50% in severe stages. Prominent subcutaneous purple venation reveals severe venous congestion and phlebitis. Requires immediate alchemical treatment with Mudcrab Chitin or Hawk Feathers before joint ossification causes permanent crippling.',
    'disease, rockjoint, tetanus, wolf, joints, combat, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 4. BRAIN ROT
(
    'brain_rot',
    'brain rot, meningitis, acute brain rot, severe brain rot, the boiling skull, witch rot, hags rot',
    'common',
    'The Boiling Skull, or Hag''s Rot. Witches, hagravens, and Forsworn hex-casters curse travelers with it in the Reach. It feels as though someone is hammering an iron spike behind your eyes and pouring scalding lard over your brain. Sufferers clutch their temples in agony, mutter nonsense, cannot bear lantern light, and ugly black necrotic sores rot across their cheeks and chin.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Colloquially known as Brain Rot, this critical condition is an acute Meningeal Encephalitis. Spread through hagraven hex-blood, crypt dampness, and infected cranial lacerations. Induces severe inflammation of the cerebral meninges and cranial channels, creating blinding cephalalgia, photophobia, and profound cognitive clouding that drains magicka capacity by up to 100 points. Late-stage tissue ischemia produces characteristic necrotic epidermal lesions on the jaw and neck. Purged through high-order Restoration magic or potions compounded from Vampire Dust and Lavender.',
    'disease, brain rot, meningitis, magicka, hagraven, reach, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 5. RATTLES
(
    'rattles',
    'rattles, pertussis, acute rattles, severe rattles, the deep hack, miners choke, whooping cough, consumption',
    'common',
    'The Deep Hack, or Miner''s Choke. Common down in dark mines and damp chaurus tunnels. Your lungs fill with foul gravel-rattle and you cough until your ribs bruise and your chest burns. When a coughing fit strikes, you fall to your knees gasping for breath with zero wind left in your sails. Comes with a bumpy, gravel-like rash along your collarbone.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Referred to by miners as the Rattles, apothecaries catalog this condition as Pertussis (Whooping Phthisis). Caused by fungal chaurus spores and stagnant subterranean dust ulcerating the tracheal and bronchial linings. Paroxysmal coughing spasms violently exhaust the diaphragm and intercostal musculature, completely halting stamina regeneration (-50% to -100%). Accompanied by a bumpy maculopapular rash across the torso. Cured through vaporized alchemical tinctures, Hawk Feathers, or divine cleansing at a temple of Kynareth.',
    'disease, rattles, pertussis, cough, stamina, chaurus, mines, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 6. WITBANE
(
    'witbane',
    'witbane, ergotism, acute witbane, severe witbane, the bruising fog, mage blight, st anthonys fire',
    'common',
    'The Bruising Fog, or Mage-Blight. You catch it from sabrecats or moldy grain in damp cellars. It brings a heavy, throbbing ache behind your forehead and dark bruises spread across your forearms without being struck. Sufferers talk slow, lose their wits, and mages find their hands went cold and their spellcraft will not gather.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known colloquially as Witbane, toxicologists document this affliction as Ergotism. Caused by fungal alkaloid blights on damp crops or toxic alkaloids in sabrecat claws. The poison induces acute neurovascular spasms in cerebral blood vessels, constricting blood flow to the brain and resulting in dark subcutaneous purpura (bruising). It suffocates the arcane channels that replenish magicka, reducing magicka recovery by up to 100%. Counteracted by alchemical vasodilators, Elixir of Cure Disease, or blessings from the Shrine of Julianos.',
    'disease, witbane, ergotism, magicka, sabrecat, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 7. DAMPWORM
(
    'dampworm',
    'dampworm, dracunculiasis, acute dampworm, severe dampworm, water worm, puddle sore, cave parasite',
    'common',
    'Water-Worm. You get it from being careless and gulping down filthy ditch-water, or wading through Falmer mud without boots. Tiny worm eggs hatch in your guts and crawl through your meat, breaking out into burning, weeping blisters all down your legs. Sufferers limp and drag their feet, barely able to walk a league.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'The affliction commoners call Dampworm is documented by naturalists as Dracunculiasis. It is contracted by ingesting unfiltered water from subterranean pools or Falmer cisterns harboring microscopic water-flea parasites. The ingested larvae burrow through the intestinal wall and mature within the subcutaneous muscle fascias of the legs. Upon maturity, the parasites induce fiery epidermal blisters (boils) to emerge, slashing movement speed by 10% to 35%. Boiling water over a campfire kills the larvae prior to consumption. Cured by Hawk Feathers or alchemical vermifuges.',
    'disease, dampworm, dracunculiasis, waterborne, falmer, speed, boils, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 8. SWAMP FEVER
(
    'swamp_fever',
    'swamp fever, the ague, acute swamp fever, severe swamp fever, the marsh ague, muck rot, paludism, bog sweat',
    'common',
    'The Marsh Ague. Lurks in the Black Marshes and the cold fens of Morthal. Mudcrabs and swamp trolls spread it through stagnant mire mud. Gives you burning night sweats followed by teeth-chattering chills, and your lower back aches so fiercely you can barely hoist a light pack without your knees buckling.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known in common parlance as Swamp Fever, classical Cyrodilic treatises designate this disease as the Ague (Paludism). Contracted through stagnant marsh water, mudcrab carapaces, and bog trolls. The miasma attacks the spleen and lumbar spinal musculature, inducing cyclic fever rigors and visceral inflammation that devastates carrying capacity by 10 to 50 points. Overlays manifest as yellowish-red epidermal boils across the torso. Neutralized by Mudcrab Chitin, blisterwort, or alchemical Cure Disease potions.',
    'disease, swamp fever, ague, carry weight, mudcrab, marsh, morthal, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 9. CHILLS
(
    'chills_disease',
    'chills, acute chills, severe chills, frost blood, the white sickness, hypothermic rigors, ice rot',
    'common',
    'The White Sickness, or Frost-Blood. Frostbite spiders and death hounds inject it with their icy fangs, or you catch it from plunging into the Sea of Ghosts. Your veins turn black-purple, your teeth chatter until they bleed, and no campfire can warm your bones. Cuts and scrapes refuse to close or scab over.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Referred to simply as the Chills by nords, this dangerous condition is Hypothermic Rigors with cryo-vascular thrombosis. Induced by frostbite spider venom, death hound bites, or prolonged exposure to freezing seas. The deep cold congeals peripheral blood in the veins, creating prominent subcutaneous purple venation and shutting down cellular regeneration (-10% to -50% health recovery). Flesh remains cold to the touch and open wounds will not heal. Requires thawing beside heat sources combined with alchemical blood-warmers or shrines of Mara.',
    'disease, chills, hypothermia, health regen, frostbite spider, sea of ghosts, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 10. FEEBLE LIMB
(
    'feeble_limb',
    'feeble limb, necrotizing fasciitis, acute feeble limb, severe feeble limb, the rotting arm, flesh slip, muscle rot',
    'common',
    'The Rotting Arm, or Flesh-Slip. Wolves and bears give it with deep fang bites, or you catch it from muddy swamp wounds. The meat in your arm turns soft and spongy, and smells like a swamp corpse. When you try to raise a shield to block a blow, your arm gives out entirely as if your muscles were made of wet flour.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known colloquially as Feeble Limb, physicians identify this dangerous condition as Necrotizing Fasciitis. Caused by virulent putrefactive bacteria introduced through deep predator puncture wounds or filthy water. The infection rapidly dissolves the fibrous tensile strength of the muscle fascias, inducing localized liquefactive necrosis (Overlay ZZRotten). Sufferers lose the structural bracing strength required to block attacks (-10% to -50% block efficacy). Demands immediate restorative healing or potent alchemical antiseptics to prevent systemic septicemia.',
    'disease, feeble limb, necrotizing, block, shield, wolf, bear, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 11. SHAKES
(
    'shakes_disease',
    'shakes, rat bite fever, acute shakes, severe shakes, skeever twitch, the tremors, skeever fever',
    'common',
    'Skeever-Twitch. You catch it when a diseased skeever sinks its yellow teeth into your leg, or from drinking dirty ditch runoff. Your hands start shaking like leaves in an autumn gale. Try to draw a bowstring and your fingers twitch so wildly the arrow flies into the dirt. Comes with belly cramps and ugly red blotches on your skin.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Commonly called the Shakes, medical treatises designate this infection as Rat-Bite Fever. Transmitted through skeever bites and sewage-contaminated runoff. The neurotoxin targets the cerebellar motor loop, producing violent kinetic intention tremors that amplify under concentration, severely degrading archery and marksman accuracy by up to 50%. Accompanied by maculopapular blotches across the abdomen and intestinal cramping. Readily counteracted by potions of Cure Disease, Hawk Feathers, or Charred Skeever Hide compounds.',
    'disease, shakes, rat bite fever, skeever, archery, tremor, blotches, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 12. WITHER
(
    'wither_disease',
    'wither, cutaneous atrophy, acute wither, severe wither, parchment flesh, the flaying, skin wither',
    'common',
    'Parchment Flesh, or the Flaying. Sabrecats spread it with their claw rakes. It thins your skin down until it looks like dry paper. Your armor chafes and cuts right into your meat, and blows that would normally bounce off your cuirass bruise you to the bone. Your skin turns blotchy and cracks under the wind.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Referred to by hunters as Wither, apothecaries catalog this condition as severe Cutaneous Atrophy. Contracted through sabrecat lacerations. The contagion breaks down dermal collagen and subcutaneous fat layers, leaving skin translucent, fragile, and unable to cushion blunt trauma. Armor rating and physical damage resistance collapse by up to 50 points. Accompanied by blotchy epidermal erythema. Treated with dermal restorative salves, Hawk Feathers, and Mudcrab Chitin.',
    'disease, wither, cutaneous atrophy, armor, damage resist, sabrecat, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 13. DROOPS
(
    'droops',
    'droops, acute droops, severe droops, the slump, ash palsy, myasthenia, morrowind sickness',
    'common',
    'The Slump, or Ash-Palsy. Native to the ash wastes of Solstheim and Red Mountain. The volcanic air and blighted ash get into your lungs and blood. Your shoulders droop, your neck goes limp, and your sword arm feels so heavy and weak that your strikes hit like wet reeds.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known in Morrowind as Droops, this Dunmeri neuromuscular affliction is an acute Ash-Induced Myasthenia. Inhaled vitrified ash particulates and fungal blights block the bodily channels that command muscular exertion. Sufferers suffer bilateral flaccid weakness in the upper extremities, reducing melee striking power by 10% to 35%. Treated with ash-salves, Teldryn''s invigorating teas, or standard Cyrodilic Cure Disease elixirs.',
    'disease, droops, myasthenia, melee, solstheim, ash, morrowind, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 14. ASTRAL VAPORS
(
    'astral_vapors',
    'astral vapors, acute astral vapors, severe astral vapors, barrow rot, tomb miasma, crypt chill, draugr curse',
    'common',
    'Tomb-Miasma, or the Barrow Curse. Lingers in sealed ancient Nordic crypts and draughts around dreadzombies. It gets into your breath and soul. Your magical energy leaks out into the cold air, and if a hostile mage hits you with a spark or frost, it burns ten times worse than ordinary flesh.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Referred to by barrow-delvers as Astral Vapors, arcane scholars classify this condition as Arcano-Miasmatic Sickness. Contracted from dreadzombies and ancient necromantic crypt air. The miasma uncouples an individual''s spiritual aura from their physical form; magicka continuously bleeds into the aether (inhibiting regeneration) while dermal magical resistance collapses, leaving the sufferer hyper-vulnerable to incoming elemental spells. Requires high-order cleansing at a shrine of Arkay or Julianos, or concentrated Void Salts and Hawk Feather compounds.',
    'disease, astral vapors, magicka, draugr, crypt, weakness to magic, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
),

-- 15. FOOD POISONING
(
    'food_poisoning',
    'food poisoning, trichinosis, gut rot, raw belly, spoiled meat sickness, wild meat cramps',
    'common',
    'Gut-Rot. Comes from being foolish enough to chew on raw wolf flank or bloody bear haunch instead of searing it over a campfire. Leaves you doubled over clutching your stomach, vomiting bile and sweating cold, while your body starves because you cannot keep a scrap of food down.',
    'healer, alchemist, scholar, priest, mage, college_of_winterhold, witch',
    'Known popularly as Food Poisoning, physicians identify this acute condition as Trichinosis and Enteric Toxicosis. Contracted by ingesting unseared predatory game (raw wolf, bear, sabrecat meat). Wild carnivores harbor encysted parasite larvae and putrefactive toxins that inflame the gastrointestinal mucosa, inducing severe dehydration, acute health reduction (-40), and an inability to absorb nourishment from food (-75% hunger recovery). Thorough cooking over a flame kills the cysts. Treated with Charcoal, Charred Skeever Hide, or digestive alchemical purgatives.',
    'disease, food poisoning, trichinosis, raw meat, health, hunger, survival, alchemy, cure disease',
    'diseases',
    'custom',
    CURRENT_TIMESTAMP
)

ON CONFLICT (topic) DO UPDATE SET
    aliases               = EXCLUDED.aliases,
    knowledge_class_basic = EXCLUDED.knowledge_class_basic,
    topic_desc_basic      = EXCLUDED.topic_desc_basic,
    knowledge_class       = EXCLUDED.knowledge_class,
    topic_desc            = EXCLUDED.topic_desc,
    tags                  = EXCLUDED.tags,
    category              = EXCLUDED.category,
    source_type           = 'custom',
    updated_at            = CURRENT_TIMESTAMP;

-- Update full-text search vectors for all 15 entries
UPDATE public.oghma
SET native_vector = 
      setweight(to_tsvector('simple', coalesce(topic, '')), 'A')
    || setweight(to_tsvector('simple', coalesce(aliases, '')), 'A')
    || setweight(to_tsvector(coalesce(topic_desc, '')), 'B')
    || setweight(to_tsvector(coalesce(topic_desc_basic, '')), 'C')
WHERE topic IN (
    'ataxia', 'bone_break_fever', 'rockjoint', 'brain_rot', 'rattles', 'witbane',
    'dampworm', 'swamp_fever', 'chills_disease', 'feeble_limb', 'shakes_disease',
    'wither_disease', 'droops', 'astral_vapors', 'food_poisoning'
);

COMMIT;

