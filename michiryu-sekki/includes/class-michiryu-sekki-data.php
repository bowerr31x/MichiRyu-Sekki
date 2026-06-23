<?php
/**
 * Sekki and ko data provider.
 *
 * @package MichiRyu_Sekki
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides 24 Sekki and 72 ko records with stable image filenames.
 */
class MichiRyu_Sekki_Data {
	/**
	 * In-request Sekki cache.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private static $seasons_cache = null;

	/**
	 * In-request Ko cache.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private static $ko_cache = null;

	/**
	 * Return all 24 Sekki.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_seasons() {
		if ( null !== self::$seasons_cache ) {
			return self::$seasons_cache;
		}

		$map_positions = array(
			1  => array( 86.0, 75.8 ),
			2  => array( 73.7, 94.7 ),
			3  => array( 43.5, 47.9 ),
			4  => array( 73.4, 73.7 ),
			5  => array( 57.4, 71.7 ),
			6  => array( 83.9, 69.7 ),
			7  => array( 59.7, 51.5 ),
			8  => array( 28.9, 58.0 ),
			9  => array( 72.9, 51.3 ),
			10 => array( 75.3, 40.3 ),
			11 => array( 89.3, 51.4 ),
			12 => array( 64.2, 36.4 ),
			13 => array( 78.4, 54.8 ),
			14 => array( 49.1, 57.0 ),
			15 => array( 62.6, 60.9 ),
			16 => array( 45.0, 40.2 ),
			17 => array( 38.7, 57.1 ),
			18 => array( 31.4, 49.5 ),
			19 => array( 26.9, 22.9 ),
			20 => array( 38.0, 27.9 ),
			21 => array( 43.5, 12.2 ),
			22 => array( 48.3, 31.5 ),
			23 => array( 43.3, 21.9 ),
			24 => array( 58.0, 21.3 ),
		);
		$raw = array(
			array( 1, 'risshun', '立春', 'Risshun', 'Beginning of Spring', '02-04', '02-18', 'Around February 4-18', 'The first signs of spring stir beneath late winter air.', 'Awakening', 'Plum branches, camellia, early willow, evergreen accents', 'Quiet upward movement with restrained brightness', 'Sekki_01_Risshun.jpg', 'Spring begins in a single opening bud.' ),
			array( 2, 'usui', '雨水', 'Usui', 'Rain Water', '02-19', '03-04', 'Around February 19-March 4', 'Snow softens into rain, and frozen ground begins to loosen.', 'Thaw', 'Pussy willow, narcissus, fern, pale blossoms', 'Gentle release and fresh moisture', 'Sekki_02_Usui.jpg', 'Rain writes the first soft line of spring.' ),
			array( 3, 'keichitsu', '啓蟄', 'Keichitsu', 'Awakening of Insects', '03-05', '03-19', 'Around March 5-19', 'Dormant life wakes as warmth gathers in the soil.', 'Emergence', 'Forsythia, quince, budding branches, moss', 'Small motions breaking the surface', 'Sekki_03_Keichitsu.jpg', 'Hidden life returns to the open air.' ),
			array( 4, 'shunbun', '春分', 'Shunbun', 'Spring Equinox', '03-20', '04-03', 'Around March 20-April 3', 'Day and night balance while spring growth becomes visible.', 'Balance', 'Cherry branches, tulip, iris leaves, young greens', 'Balanced lines with a clear point of bloom', 'Sekki_04_Shunbun.jpg', 'Light and shadow meet at spring’s center.' ),
			array( 5, 'seimei', '清明', 'Seimei', 'Clear and Bright', '04-04', '04-19', 'Around April 4-19', 'Air clears, blossoms open, and the world feels freshly washed.', 'Clarity', 'Dogwood, cherry, anemone, fresh grasses', 'Open space, clean stems, and bright accents', 'Sekki_05_Seimei.jpg', 'A clear sky gathers in every petal.' ),
			array( 6, 'kokuu', '穀雨', 'Kokuu', 'Grain Rain', '04-20', '05-04', 'Around April 20-May 4', 'Spring rain nourishes seeds, shoots, and fields.', 'Nourishment', 'Wisteria, peony, hosta leaves, rain-washed branches', 'Layered abundance grounded by fresh green mass', 'Sekki_06_Kokuu.jpg', 'Rain feeds the promise of the field.' ),
			array( 7, 'rikka', '立夏', 'Rikka', 'Beginning of Summer', '05-05', '05-20', 'Around May 5-20', 'Summer begins with quickening green and lengthening light.', 'Fresh vigor', 'Iris, maple leaves, lily of the valley, young branches', 'Crisp verticals and lively new foliage', 'Sekki_07_Rikka.jpg', 'Green steps boldly into the light.' ),
			array( 8, 'shoman', '小満', 'Shoman', 'Lesser Fullness', '05-21', '06-04', 'Around May 21-June 4', 'Living things fill out, not yet at peak, but visibly abundant.', 'Growing fullness', 'Peony, hydrangea bud, grasses, viburnum, leafy branches', 'Rounded volume balanced with airy negative space', 'Sekki_08_Shoman.jpg', 'Fullness gathers before it overflows.' ),
			array( 9, 'boshu', '芒種', 'Boshu', 'Grain in Ear', '06-05', '06-20', 'Around June 5-20', 'Grain heads form and the season turns toward humid growth.', 'Cultivation', 'Grasses, allium, iris, reeds, seed heads', 'Textured rhythm and field-like repetition', 'Sekki_09_Boshu.jpg', 'Seeds remember the shape of harvest.' ),
			array( 10, 'geshi', '夏至', 'Geshi', 'Summer Solstice', '06-21', '07-06', 'Around June 21-July 6', 'The longest light of the year stretches over early summer.', 'Radiance', 'Sunflower, smoke bush, grasses, broad leaves', 'Bold light, high reach, and confident asymmetry', 'Sekki_10_Geshi.jpg', 'The day opens to its widest breath.' ),
			array( 11, 'shosho', '小暑', 'Shosho', 'Lesser Heat', '07-07', '07-22', 'Around July 7-22', 'Heat rises gradually and summer colors deepen.', 'Warming intensity', 'Lotus leaf, zinnia, grasses, fern, cooling foliage', 'A cool counterpoint to gathering heat', 'Sekki_11_Shosho.jpg', 'Heat arrives in widening circles.' ),
			array( 12, 'taisho', '大暑', 'Taisho', 'Greater Heat', '07-23', '08-06', 'Around July 23-August 6', 'The strongest summer heat asks for shade, water, and restraint.', 'Peak heat', 'Lotus, water plants, hosta, tropical leaves, minimal blooms', 'Cool emptiness and deliberate simplicity', 'Sekki_12_Taisho.jpg', 'Stillness becomes a form of coolness.' ),
			array( 13, 'risshu', '立秋', 'Risshu', 'Beginning of Autumn', '08-07', '08-22', 'Around August 7-22', 'Autumn begins quietly while summer heat still lingers.', 'First autumn', 'Miscanthus, early chrysanthemum, seed pods, airy grasses', 'A subtle turning with lighter, drier textures', 'Sekki_13_Risshu.jpg', 'Autumn begins as a whisper in the heat.' ),
			array( 14, 'shosho-autumn', '処暑', 'Shosho', 'Limit of Heat', '08-23', '09-06', 'Around August 23-September 6', 'Heat begins to ease, and evenings carry a gentler edge.', 'Easing heat', 'Aster, grasses, bittersweet vine, seed heads', 'Release, loosened lines, and softened color', 'Sekki_14_Shosho.jpg', 'Summer loosens its hold on the evening air.' ),
			array( 15, 'hakuro', '白露', 'Hakuro', 'White Dew', '09-07', '09-22', 'Around September 7-22', 'Morning dew brightens grasses as autumn air clarifies.', 'Dew and clarity', 'Silver grass, chrysanthemum, sedge, white flowers', 'Fine textures, pale accents, and reflective quiet', 'Sekki_15_Hakuro.jpg', 'Dew makes the morning visible.' ),
			array( 16, 'shubun', '秋分', 'Shubun', 'Autumn Equinox', '09-23', '10-07', 'Around September 23-October 7', 'Day and night balance again as autumn deepens.', 'Autumn balance', 'Chrysanthemum, maple, grasses, branch with berries', 'Measured contrast between bloom and branch', 'Sekki_16_Shubun.jpg', 'Balance returns with autumn color.' ),
			array( 17, 'kanro', '寒露', 'Kanro', 'Cold Dew', '10-08', '10-22', 'Around October 8-22', 'Dew grows colder, colors sharpen, and autumn settles in.', 'Crispness', 'Maple, persimmon branch, chrysanthemum, dried grasses', 'Clear contrast and a crisp seasonal edge', 'Sekki_17_Kanro.jpg', 'Cold dew sharpens the color of things.' ),
			array( 18, 'soko', '霜降', 'Soko', 'Frost Descent', '10-23', '11-06', 'Around October 23-November 6', 'First frosts appear, marking the final richness of autumn.', 'Frost and maturity', 'Oak leaves, berries, dried lotus, late chrysanthemum', 'Weathered texture and mature color', 'Sekki_18_Soko.jpg', 'Frost traces the edge of autumn.' ),
			array( 19, 'ritto', '立冬', 'Ritto', 'Beginning of Winter', '11-07', '11-21', 'Around November 7-21', 'Winter begins in bare branches and quieter light.', 'Entering stillness', 'Pine, bare branch, camellia leaf, dried seed pods', 'Sparse structure and composed strength', 'Sekki_19_Ritto.jpg', 'Winter enters through the branch line.' ),
			array( 20, 'shosetsu', '小雪', 'Shosetsu', 'Lesser Snow', '11-22', '12-06', 'Around November 22-December 6', 'Light snow may begin, and the palette becomes quieter.', 'First snow', 'Pine, white chrysanthemum, birch twig, silver foliage', 'Pale restraint with evergreen steadiness', 'Sekki_20_Shosetsu.jpg', 'A first snow softens the world’s outline.' ),
			array( 21, 'taisetsu', '大雪', 'Taisetsu', 'Greater Snow', '12-07', '12-20', 'Around December 7-20', 'Snow season deepens, and winter’s presence becomes unmistakable.', 'Deepening winter', 'Pine, cedar, red berries, white flowers, bare branch', 'Strong winter structure with small points of color', 'Sekki_21_Taisetsu.jpg', 'Snow gives silence a visible form.' ),
			array( 22, 'toji', '冬至', 'Toji', 'Winter Solstice', '12-21', '01-04', 'Around December 21-January 4', 'The year reaches its longest night, and light begins returning.', 'Return of light', 'Pine, yuzu, nandina berries, bare branch, camellia', 'Deep stillness with a single bright promise', 'Sekki_22_Toji.jpg', 'From the longest night, light turns homeward.' ),
			array( 23, 'shokan', '小寒', 'Shokan', 'Lesser Cold', '01-05', '01-19', 'Around January 5-19', 'Cold strengthens after the new year, crisp and spare.', 'Clear cold', 'Pine, bamboo, plum bud, willow, white blooms', 'Clean austerity with signs of resilience', 'Sekki_23_Shokan.jpg', 'Cold air clarifies the shape of the year.' ),
			array( 24, 'daikan', '大寒', 'Daikan', 'Greater Cold', '01-20', '02-03', 'Around January 20-February 3', 'The deepest cold comes just before spring begins again.', 'Deep cold', 'Pine, plum bud, camellia, winter branches, moss', 'Dense quiet with a hidden point of renewal', 'Sekki_24_Daikan.jpg', 'At cold’s depth, spring waits unseen.' ),
		);

		self::$seasons_cache = array_map(
			function ( $row ) use ( $map_positions ) {
				return self::format_season( $row, $map_positions[ (int) $row[0] ] ?? array( 50, 50 ) );
			},
			$raw
		);

		return self::$seasons_cache;
	}

	/**
	 * Return all 72 ko microseasons.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_ko() {
		if ( null !== self::$ko_cache ) {
			return self::$ko_cache;
		}

		$names = array(
			array( '東風解凍', 'Harukaze kori o toku', 'East Wind Melts Ice', 'EastWindMeltsIce' ),
			array( '黄鶯睍睆', 'Kou kenkan su', 'Bush Warbler Sings', 'BushWarblerSings' ),
			array( '魚上氷', 'Uo kori o izuru', 'Fish Emerge From Ice', 'FishEmergeFromIce' ),
			array( '土脉潤起', 'Tsuchi no shou uruoi okoru', 'Rain Moistens The Soil', 'RainMoistensTheSoil' ),
			array( '霞始靆', 'Kasumi hajimete tanabiku', 'Mist Starts To Linger', 'MistStartsToLinger' ),
			array( '草木萌動', 'Soumoku mebae izuru', 'Grass And Trees Begin To Sprout', 'GrassAndTreesBeginToSprout' ),
			array( '蟄虫啓戸', 'Sugomori mushito o hiraku', 'Hibernating Insects Surface', 'HibernatingInsectsSurface' ),
			array( '桃始笑', 'Momo hajimete saku', 'Peach Blossoms', 'PeachBlossoms' ),
			array( '菜虫化蝶', 'Namushi chou to naru', 'Caterpillars Become Butterflies', 'CaterpillarsBecomeButterflies' ),
			array( '雀始巣', 'Suzume hajimete sukau', 'Sparrows Start To Nest', 'SparrowsStartToNest' ),
			array( '桜始開', 'Sakura hajimete hiraku', 'Cherry Blossoms Open', 'CherryBlossomsOpen' ),
			array( '雷乃発声', 'Kaminari sunawachi koe o hassu', 'First Thunder Sounds', 'FirstThunderSounds' ),
			array( '玄鳥至', 'Tsubame kitaru', 'Swallows Arrive', 'SwallowsArrive' ),
			array( '鴻雁北', 'Kougan kaeru', 'Wild Geese Fly North', 'WildGeeseFlyNorth' ),
			array( '虹始見', 'Niji hajimete arawaru', 'First Rainbows Appear', 'FirstRainbowsAppear' ),
			array( '葭始生', 'Ashi hajimete shouzu', 'Reeds Begin To Sprout', 'ReedsBeginToSprout' ),
			array( '霜止出苗', 'Shimo yamite nae izuru', 'Frost Ends And Seedlings Grow', 'FrostEndsAndSeedlingsGrow' ),
			array( '牡丹華', 'Botan hana saku', 'Peonies Bloom', 'PeoniesBloom' ),
			array( '蛙始鳴', 'Kawazu hajimete naku', 'Frogs Start Singing', 'FrogsStartSinging' ),
			array( '蚯蚓出', 'Mimizu izuru', 'Earthworms Surface', 'EarthwormsSurface' ),
			array( '竹笋生', 'Takenoko shouzu', 'Bamboo Shoots Sprout', 'BambooShootsSprout' ),
			array( '蚕起食桑', 'Kaiko okite kuwa o hamu', 'Silkworms Eat Mulberry Leaves', 'SilkwormsEatMulberryLeaves' ),
			array( '紅花栄', 'Benibana sakau', 'Safflowers Bloom', 'SafflowersBloom' ),
			array( '麦秋至', 'Mugi no toki itaru', 'Wheat Ripens', 'WheatRipens' ),
			array( '螳螂生', 'Kamakiri shouzu', 'Praying Mantises Hatch', 'PrayingMantisesHatch' ),
			array( '腐草為蛍', 'Kusaretaru kusa hotaru to naru', 'Rotten Grass Becomes Fireflies', 'RottenGrassBecomesFireflies' ),
			array( '梅子黄', 'Ume no mi kibamu', 'Plums Turn Yellow', 'PlumsTurnYellow' ),
			array( '乃東枯', 'Natsukarekusa karuru', 'Self-Heal Withers', 'SelfHealWithers' ),
			array( '菖蒲華', 'Ayame hana saku', 'Irises Bloom', 'IrisesBloom' ),
			array( '半夏生', 'Hange shouzu', 'Crow Dipper Sprouts', 'CrowDipperSprouts' ),
			array( '温風至', 'Atsukaze itaru', 'Warm Winds Blow', 'WarmWindsBlow' ),
			array( '蓮始開', 'Hasu hajimete hiraku', 'Lotus Flowers Open', 'LotusFlowersOpen' ),
			array( '鷹乃学習', 'Taka sunawachi waza o narau', 'Young Hawks Learn To Fly', 'YoungHawksLearnToFly' ),
			array( '桐始結花', 'Kiri hajimete hana o musubu', 'Paulownia Forms Seeds', 'PaulowniaFormsSeeds' ),
			array( '土潤溽暑', 'Tsuchi uruoute mushi atsushi', 'Earth Is Damp And Humid', 'EarthIsDampAndHumid' ),
			array( '大雨時行', 'Taiu toki doki furu', 'Heavy Rain Sometimes Falls', 'HeavyRainSometimesFalls' ),
			array( '涼風至', 'Suzukaze itaru', 'Cool Winds Blow', 'CoolWindsBlow' ),
			array( '寒蝉鳴', 'Higurashi naku', 'Evening Cicadas Sing', 'EveningCicadasSing' ),
			array( '蒙霧升降', 'Fukaki kiri matou', 'Thick Fog Descends', 'ThickFogDescends' ),
			array( '綿柎開', 'Wata no hana shibe hiraku', 'Cotton Bolls Open', 'CottonBollsOpen' ),
			array( '天地始粛', 'Tenchi hajimete samushi', 'Heat Begins To Settle', 'HeatBeginsToSettle' ),
			array( '禾乃登', 'Kokumono sunawachi minoru', 'Rice Ripens', 'RiceRipens' ),
			array( '草露白', 'Kusa no tsuyu shiroshi', 'Dew Gleams White On Grass', 'DewGleamsWhiteOnGrass' ),
			array( '鶺鴒鳴', 'Sekirei naku', 'Wagtails Sing', 'WagtailsSing' ),
			array( '玄鳥去', 'Tsubame saru', 'Swallows Leave', 'SwallowsLeave' ),
			array( '雷乃収声', 'Kaminari sunawachi koe o osamu', 'Thunder Ceases', 'ThunderCeases' ),
			array( '蟄虫坏戸', 'Mushi kakurete to o fusagu', 'Insects Seal Their Burrows', 'InsectsSealTheirBurrows' ),
			array( '水始涸', 'Mizu hajimete karuru', 'Fields Drain', 'FieldsDrain' ),
			array( '鴻雁来', 'Kougan kitaru', 'Wild Geese Return', 'WildGeeseReturn' ),
			array( '菊花開', 'Kiku no hana hiraku', 'Chrysanthemums Bloom', 'ChrysanthemumsBloom' ),
			array( '蟋蟀在戸', 'Kirigirisu to ni ari', 'Crickets Chirp Near Doors', 'CricketsChirpNearDoors' ),
			array( '霜始降', 'Shimo hajimete furu', 'First Frost Falls', 'FirstFrostFalls' ),
			array( '霎時施', 'Kosame toki doki furu', 'Light Rain Falls', 'LightRainFalls' ),
			array( '楓蔦黄', 'Momiji tsuta kibamu', 'Maple And Ivy Turn Yellow', 'MapleAndIvyTurnYellow' ),
			array( '山茶始開', 'Tsubaki hajimete hiraku', 'Camellias Begin To Bloom', 'CamelliasBeginToBloom' ),
			array( '地始凍', 'Chi hajimete kooru', 'Earth Begins To Freeze', 'EarthBeginsToFreeze' ),
			array( '金盞香', 'Kinsenka saku', 'Daffodils Bloom', 'DaffodilsBloom' ),
			array( '虹蔵不見', 'Niji kakurete miezu', 'Rainbows Hide', 'RainbowsHide' ),
			array( '朔風払葉', 'Kitakaze konoha o harau', 'North Wind Clears Leaves', 'NorthWindClearsLeaves' ),
			array( '橘始黄', 'Tachibana hajimete kibamu', 'Citrus Turns Yellow', 'CitrusTurnsYellow' ),
			array( '閉塞成冬', 'Sora samuku fuyu to naru', 'Sky Closes Into Winter', 'SkyClosesIntoWinter' ),
			array( '熊蟄穴', 'Kuma ana ni komoru', 'Bears Enter Dens', 'BearsEnterDens' ),
			array( '鱖魚群', 'Sake no uo muragaru', 'Salmon Gather', 'SalmonGather' ),
			array( '乃東生', 'Natsukarekusa shouzu', 'Self-Heal Sprouts', 'SelfHealSprouts' ),
			array( '麋角解', 'Sawashika no tsuno otsuru', 'Deer Shed Antlers', 'DeerShedAntlers' ),
			array( '雪下出麦', 'Yuki watarite mugi nobiru', 'Wheat Sprouts Under Snow', 'WheatSproutsUnderSnow' ),
			array( '芹乃栄', 'Seri sunawachi sakau', 'Water Parsley Flourishes', 'WaterParsleyFlourishes' ),
			array( '水泉動', 'Shimizu atataka o fukumu', 'Springs Begin To Stir', 'SpringsBeginToStir' ),
			array( '雉始雊', 'Kiji hajimete naku', 'Pheasants Start Calling', 'PheasantsStartCalling' ),
			array( '款冬華', 'Fuki no hana saku', 'Butterbur Buds Bloom', 'ButterburBudsBloom' ),
			array( '水沢腹堅', 'Sawa mizu koori tsumeru', 'Streams Freeze Over', 'StreamsFreezeOver' ),
			array( '鶏始乳', 'Niwatori hajimete toyatsuku', 'Hens Begin Laying', 'HensBeginLaying' ),
		);

		$records = array();
		foreach ( $names as $index => $name ) {
			$ko_number           = $index + 1;
			$parent_sekki_number = (int) ceil( $ko_number / 3 );
			$records[]           = self::format_ko( $ko_number, $parent_sekki_number, $name );
		}

		self::$ko_cache = $records;

		return self::$ko_cache;
	}

	/**
	 * Get one season by slug.
	 *
	 * @param string $slug Season slug.
	 * @return array<string,mixed>|null
	 */
	public static function get_by_slug( $slug ) {
		foreach ( self::get_seasons() as $season ) {
			if ( $slug === $season['slug'] ) {
				return $season;
			}
		}

		return null;
	}

	/**
	 * Determine current Sekki by month and day.
	 *
	 * @param int|null    $timestamp Optional UTC Unix timestamp.
	 * @param string|null $timezone Optional display timezone.
	 * @return array<string,mixed>
	 */
	public static function get_current( $timestamp = null, $timezone = null ) {
		$timestamp = $timestamp ? absint( $timestamp ) : time();
		$today     = self::format_timestamp( $timestamp, $timezone, 'm-d' );

		foreach ( self::get_seasons() as $season ) {
			if ( self::date_in_range( $today, $season['start'], $season['end'] ) ) {
				return $season;
			}
		}

		return self::get_seasons()[0];
	}

	/**
	 * Determine current ko by month and day.
	 *
	 * @param int|null    $timestamp Optional UTC Unix timestamp.
	 * @param string|null $timezone Optional display timezone.
	 * @return array<string,mixed>
	 */
	public static function get_current_ko( $timestamp = null, $timezone = null ) {
		$timestamp = $timestamp ? absint( $timestamp ) : time();
		$today     = self::format_timestamp( $timestamp, $timezone, 'm-d' );

		foreach ( self::get_ko() as $ko ) {
			if ( self::date_in_range( $today, $ko['start'], $ko['end'] ) ) {
				return $ko;
			}
		}

		return self::get_ko()[0];
	}

	/**
	 * Return the next Sekki after the given season.
	 *
	 * @param string $slug Current season slug.
	 * @return array<string,mixed>
	 */
	public static function get_next( $slug ) {
		$seasons = self::get_seasons();
		foreach ( $seasons as $index => $season ) {
			if ( $slug === $season['slug'] ) {
				$next_index = ( $index + 1 ) % count( $seasons );
				return $seasons[ $next_index ];
			}
		}

		return $seasons[0];
	}

	/**
	 * Return the previous Sekki before the given season.
	 *
	 * @param string $slug Current season slug.
	 * @return array<string,mixed>
	 */
	public static function get_previous( $slug ) {
		$seasons = self::get_seasons();
		foreach ( $seasons as $index => $season ) {
			if ( $slug === $season['slug'] ) {
				$previous_index = ( $index - 1 + count( $seasons ) ) % count( $seasons );
				return $seasons[ $previous_index ];
			}
		}

		return $seasons[ count( $seasons ) - 1 ];
	}

	/**
	 * Calculate days until the next Sekki starts.
	 *
	 * @param array<string,mixed> $current Current season.
	 * @param int|null           $timestamp Optional UTC Unix timestamp.
	 * @param string|null        $timezone Optional display timezone.
	 * @return int
	 */
	public static function days_until_next( $current, $timestamp = null, $timezone = null ) {
		$timestamp = $timestamp ? absint( $timestamp ) : time();
		$timezone_object = self::get_timezone_object( $timezone );
		$now       = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone_object );
		$next      = self::get_next( $current['slug'] );
		$year      = (int) $now->format( 'Y' );
		$target    = self::create_local_midnight_timestamp( $year, $next['start'], $timezone_object );

		if ( null === $target || $target <= $timestamp ) {
			$target = self::create_local_midnight_timestamp( $year + 1, $next['start'], $timezone_object );
		}

		if ( null === $target ) {
			return 0;
		}

		return (int) ceil( ( $target - $timestamp ) / DAY_IN_SECONDS );
	}

	/**
	 * Format a raw Sekki record.
	 *
	 * @param array<int,mixed> $row Raw row.
	 * @return array<string,mixed>
	 */
	private static function format_season( $row, $map_position = array( 50, 50 ) ) {
		$number = (int) $row[0];

		return array(
			'sekki_number'           => $number,
			'number'                 => $number,
			'slug'                   => $row[1],
			'kanji'                  => $row[2],
			'romaji'                 => $row[3],
			'english_name'           => $row[4],
			'start'                  => $row[5],
			'end'                    => $row[6],
			'date_range'             => __( $row[7], 'michiryu-sekki' ),
			'approximate_date_range' => __( $row[7], 'michiryu-sekki' ),
			'description'            => __( $row[8], 'michiryu-sekki' ),
			'theme'                  => __( $row[9], 'michiryu-sekki' ),
			'materials'              => __( $row[10], 'michiryu-sekki' ),
			'mood'                   => __( $row[11], 'michiryu-sekki' ),
			'image_file'             => $row[12],
			'phrase'                 => __( $row[13], 'michiryu-sekki' ),
			'short_phrase'           => __( $row[13], 'michiryu-sekki' ),
			'map_x_percent'          => (float) $map_position[0],
			'map_y_percent'          => (float) $map_position[1],
			'detail_url'             => '',
			'related_ko'             => array( ( $number * 3 ) - 2, ( $number * 3 ) - 1, $number * 3 ),
			'ko'                     => array(),
			'local_notes'            => array(),
		);
	}

	/**
	 * Format a ko record.
	 *
	 * @param int              $ko_number Ko number.
	 * @param int              $parent_sekki_number Parent Sekki number.
	 * @param array<int,string> $name Name fields.
	 * @return array<string,mixed>
	 */
	private static function format_ko( $ko_number, $parent_sekki_number, $name ) {
		$range = self::get_ko_range( $parent_sekki_number, ( $ko_number - 1 ) % 3 );

		return array(
			'ko_number'           => $ko_number,
			'parent_sekki_number' => $parent_sekki_number,
			'kanji'               => $name[0],
			'romaji'              => $name[1],
			'english_name'        => $name[2],
			'start'               => $range['start'],
			'end'                 => $range['end'],
			'date_range'          => $range['date_range'],
			'icon_file'           => sprintf( 'KO_%02d_%s.png', $ko_number, $name[3] ),
			'short_description'   => sprintf(
				/* translators: %s: ko English name. */
				__( 'A five-day microseason: %s.', 'michiryu-sekki' ),
				$name[2]
			),
		);
	}

	/**
	 * Build an approximate five-day ko date range from its parent Sekki.
	 *
	 * @param int $parent_sekki_number Parent Sekki number.
	 * @param int $offset_index Offset 0, 1, or 2.
	 * @return array<string,string>
	 */
	private static function get_ko_range( $parent_sekki_number, $offset_index ) {
		$season = self::get_seasons()[ $parent_sekki_number - 1 ];
		$year   = '2001';

		if ( '01' === substr( $season['start'], 0, 2 ) ) {
			$year = '2002';
		}

		$start = strtotime( $year . '-' . $season['start'] . ' +' . ( $offset_index * 5 ) . ' days' );
		$end   = strtotime( $year . '-' . $season['start'] . ' +' . ( ( $offset_index * 5 ) + 4 ) . ' days' );

		if ( false === $start || false === $end ) {
			return array(
				'start'      => $season['start'],
				'end'        => $season['end'],
				'date_range' => $season['date_range'],
			);
		}

		return array(
			'start'      => gmdate( 'm-d', $start ),
			'end'        => gmdate( 'm-d', $end ),
			'date_range' => gmdate( 'M j', $start ) . '-' . gmdate( 'M j', $end ),
		);
	}

	/**
	 * Check a month-day string against a date range that may cross year end.
	 *
	 * @param string $today Current m-d value.
	 * @param string $start Start m-d value.
	 * @param string $end End m-d value.
	 * @return bool
	 */
	private static function date_in_range( $today, $start, $end ) {
		if ( $start <= $end ) {
			return $today >= $start && $today <= $end;
		}

		return $today >= $start || $today <= $end;
	}

	/**
	 * Format a UTC timestamp in a display timezone.
	 *
	 * @param int         $timestamp UTC Unix timestamp.
	 * @param string|null $timezone Optional timezone string.
	 * @param string      $format Date format.
	 * @return string
	 */
	private static function format_timestamp( $timestamp, $timezone, $format ) {
		return ( new DateTimeImmutable( '@' . absint( $timestamp ) ) )
			->setTimezone( self::get_timezone_object( $timezone ) )
			->format( $format );
	}

	/**
	 * Build a timezone object, falling back to the WordPress site timezone.
	 *
	 * @param string|null $timezone Optional timezone string.
	 * @return DateTimeZone
	 */
	private static function get_timezone_object( $timezone = null ) {
		if ( is_string( $timezone ) && '' !== $timezone ) {
			try {
				return new DateTimeZone( $timezone );
			} catch ( Exception $exception ) {
				// Fall back below.
			}
		}

		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		return new DateTimeZone( 'UTC' );
	}

	/**
	 * Create a UTC timestamp for local midnight in a timezone.
	 *
	 * @param int          $year Year.
	 * @param string       $month_day Month-day string.
	 * @param DateTimeZone $timezone Display timezone.
	 * @return int|null
	 */
	private static function create_local_midnight_timestamp( $year, $month_day, $timezone ) {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', absint( $year ) . '-' . $month_day . ' 00:00:00', $timezone );

		return $date instanceof DateTimeImmutable ? $date->getTimestamp() : null;
	}
}
