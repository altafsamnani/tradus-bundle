<?php

namespace TradusBundle\Entity;

/**
 * Interface OfferInterface.
 */
interface OfferInterface
{
    public const SUPPORTED_LOCALES = ['en', 'nl', 'pl', 'ro', 'pt-pt', 'ru', 'es', 'it', 'fr', 'de', 'sk', 'fl', 'nl-be', 'lt', 'uk', 'tr', 'el', 'hu', 'sr', 'da', 'hr', 'bg'];
    public const LOCALE_POLAND = 'pl';
    // Fields.
    public const FIELD_SELLER = 'seller';
    public const FIELD_OFFER_ID = 'offer_id';
    public const FIELD_V1_OFFER_ID = 'offer_id_v1';
    public const FIELD_AD_ID = 'ad_id';
    public const FIELD_TPRO_ID = 'tpro_id';
    public const FIELD_LECTURA_ID = 'lectura_id';
    public const FIELD_MODEL = 'model';
    public const FIELD_MODEL_NAME = 'model_name';
    public const FIELD_VERSION_NAME = 'version_name';
    public const FIELD_CATEGORY = 'category';
    public const FIELD_MAKE = 'make';
    public const FIELD_TYPE_AD = 'type_ad';
    public const FIELD_V1_TYPE_AD = 'typeAd';
    public const FIELD_PRICE = 'price';
    public const FIELD_CURRENCY = 'currency';
    public const FIELD_URL = 'url';
    public const FIELD_VIDEO_URL = 'video_url';
    public const FIELD_EXTRA = 'extra';
    public const FIELD_DESCRIPTIONS = 'descriptions';
    public const FIELD_V1_DESCRIPTIONS = 'description';
    public const FIELD_SLUG = 'slug';
    public const FIELD_IMAGES = 'images';
    public const FIELD_DEPRECIATION = 'depreciation';
    public const FIELD_STATUS = 'status';
    public const FIELD_IMAGE_DUPLICATED = 'image_duplicated';
    public const FIELD_CREATED_AT = 'created_at';
    public const FIELD_BUMPED_AT = 'bumped_at';
    public const FIELD_COUNTRY = 'country';
    public const FIELD_PRICE_ANALYSIS_TYPE = 'price_analysis_type';
    public const FIELD_PRICE_ANALYSIS_VALUE = 'price_analysis_value';
    public const FIELD_PRICE_ANALYSIS_DATA = 'price_analysis_data';
    public const FIELD_POSE_STATUS = 'pose_status';
    public const FIELD_SITECODE = 'sitecode';

    // Extra fields.
    public const FIELD_PRICE_TYPE = 'price_type';
    public const FIELD_V1_PRICE_TYPE = 'priceType';
    public const FIELD_CONSTRUCTION_YEAR = 'construction_year';
    public const FIELD_V1_CONSTRUCTION_YEAR = 'constructionYear';
    public const FIELD_HOURS_RUN = 'hours_run';
    public const FIELD_V1_HOURS_RUN = 'hoursRun';
    public const FIELD_SELLERS_REFERENCE = 'sellers_reference';
    public const FIELD_V1_SELLERS_REFERENCE = 'sellersReference';
    public const FIELD_WEIGHT = 'weight';
    public const FIELD_VAT_RECLAIMABLE = 'vat_reclaimable';
    public const FIELD_V1_VAT_RECLAIMABLE = 'vatReclaimable';
    public const FIELD_PRICE_EXCL_VAT = 'price_excl_vat';
    public const FIELD_V1_PRICE_EXCL_VAT = 'priceExclVat';
    public const FIELD_CONSTRUCTION_MONTH = 'construction_month';
    public const FIELD_V1_CONSTRUCTION_MONTH = 'constructionMonth';
    public const FIELD_MILEAGE = 'mileage';
    public const FIELD_HEIGHT = 'height';
    public const FIELD_LENGTH = 'length';
    public const FIELD_WIDTH = 'width';
    public const FIELD_SERIAL = 'serial';
    public const FIELD_CONTACT_INFORMATION = 'contact_information';
    public const FIELD_V1_CONTACT_INFORMATION = 'contactInformation';
    public const FIELD_BUCKET_CAPACITY = 'bucket_capacity';
    public const FIELD_V1_BUCKET_CAPACITY = 'bucketCapacity';
    public const FIELD_ADDITIONAL_HYDRAULICS = 'additional_hydraulics';
    public const FIELD_V1_ADDITIONAL_HYDRAULICS = 'additionalHydraulics';
    public const FIELD_OUTPUT_AUXILIARY_HYDRAULICS = 'output_auxiliary_hydraulics';
    public const FIELD_V1_OUTPUT_AUXILIARY_HYDRAULICS = 'outputAuxiliaryHydraulics';
    public const FIELD_DIPPER_STICK_LENGTH = 'dipper_stick_length';
    public const FIELD_V1_DIPPER_STICK_LENGTH = 'dipperStickLength';
    public const FIELD_MAX_LIFT_HEIGHT = 'max_lift_height';
    public const FIELD_V1_MAX_LIFT_HEIGHT = 'maxLiftHeight';
    public const FIELD_JIB_LENGTH_CRANE = 'jib_length_crane';
    public const FIELD_V1_JIB_LENGTH_CRANE = 'jibLengthCrane';
    public const FIELD_MAX_HORIZONTAL_REACH = 'max_horizontal_reach';
    public const FIELD_V1_MAX_HORIZONTAL_REACH = 'maxHorizontalReach';
    public const FIELD_TANK_VOLUME = 'tank_volume';
    public const FIELD_V1_TANK_VOLUME = 'tankVolume';
    public const FIELD_FITS_FOLLOWING_MACHINES = 'fits_following_machines';
    public const FIELD_V1_FITS_FOLLOWING_MACHINES = 'fitsFollowingMachines';
    public const FIELD_REQUIRED_HYDRAULIC_FLOW = 'required_hydraulic_flow';
    public const FIELD_V1_REQUIRED_HYDRAULIC_FLOW = 'requiredHydraulicFlow';
    public const FIELD_WORKING_PRESSURE = 'working_pressure';
    public const FIELD_V1_WORKING_PRESSURE = 'workingPressure';
    public const FIELD_ENGINE_MODEL = 'engine_model';
    public const FIELD_V1_ENGINE_MODEL = 'engineModel';
    public const FIELD_NET_POWER = 'net_power';
    public const FIELD_V1_NET_POWER = 'netPower';
    public const FIELD_MAX_LIFT_CAPACITY = 'max_lift_capacity';
    public const FIELD_V1_MAX_LIFT_CAPACITY = 'maxLiftCapacity';
    public const FIELD_GENERATOR_MODEL = 'generator_model';
    public const FIELD_V1_GENERATOR_MODEL = 'generatorModel';
    public const FIELD_FRONT_TIRE_SIZE = 'front_tire_size';
    public const FIELD_V1_FRONT_TIRE_SIZE = 'frontTireSize';
    public const FIELD_REAR_TIRE_SIZE = 'rear_tire_size';
    public const FIELD_V1_REAR_TIRE_SIZE = 'rearTireSize';
    public const FIELD_WORKING_WIDTH = 'working_width';
    public const FIELD_V1_WORKING_WIDTH = 'workingWidth';
    public const FIELD_CUTTING_WIDTH = 'cutting_width';
    public const FIELD_V1_CUTTING_WIDTH = 'cuttingWidth';
    public const FIELD_NUMBER_ROWS = 'number_rows';
    public const FIELD_V1_NUMBER_ROWS = 'numberRows';
    public const FIELD_WORKING_WIDTH_ROWS = 'working_width_rows';
    public const FIELD_V1_WORKING_WIDTH_ROWS = 'workingWidthRows';
    public const FIELD_CAPACITY = 'capacity';
    public const FIELD_MAX_POWER = 'max_power';
    public const FIELD_V1_MAX_POWER = 'maxPower';
    public const FIELD_TOP_SPEED = 'top_speed';
    public const FIELD_V1_TOP_SPEED = 'topSpeed';
    public const FIELD_POWER_TAKE_OFF = 'power_take_off';
    public const FIELD_V1_POWER_TAKE_OFF = 'powerTakeOff';
    public const FIELD_CARGO_SPACE_LENGTH = 'cargo_space_length';
    public const FIELD_V1_CARGO_SPACE_LENGTH = 'cargoSpaceLength';
    public const FIELD_CARGO_SPACE_HEIGHT = 'cargo_space_height';
    public const FIELD_V1_CARGO_SPACE_HEIGHT = 'cargoSpaceHeight';
    public const FIELD_CARGO_SPACE_WIDTH = 'cargo_space_width';
    public const FIELD_V1_CARGO_SPACE_WIDTH = 'cargoSpaceWidth';
    public const FIELD_WHEEL_BASE = 'wheel_base';
    public const FIELD_V1_WHEEL_BASE = 'wheelBase';
    public const FIELD_MAX_PAYLOAD = 'max_payload';
    public const FIELD_V1_MAX_PAYLOAD = 'maxPayload';
    public const FIELD_NO_DOORS = 'no_doors';
    public const FIELD_V1_NO_DOORS = 'noDoors';
    public const FIELD_NO_SEATS = 'no_seats';
    public const FIELD_V1_NO_SEATS = 'noSeats';
    public const FIELD_NO_STANDING = 'no_standing';
    public const FIELD_V1_NO_STANDING = 'noStanding';
    public const FIELD_ENGINE_CAPACITY_DISPLACEMENT = 'engine_capacity_displacement';
    public const FIELD_V1_ENGINE_CAPACITY_DISPLACEMENT = 'engineCapacityDisplacement';
    public const FIELD_VOLTAGE = 'voltage';
    public const FIELD_MAX_AXLE_WEIGHT = 'max_axle_weight';
    public const FIELD_V1_MAX_AXLE_WEIGHT = 'maxAxleWeight';
    public const FIELD_DISC_BRAKES = 'disc_brakes';
    public const FIELD_V1_DISC_BRAKES = 'discBrakes';
    public const FIELD_RPM = 'rpm';
    public const FIELD_OTHER_OPTIONS_ATTACHMENTS = 'other_options_attachments';
    public const FIELD_V1_OTHER_OPTIONS_ATTACHMENTS = 'otherOptionsAttachments';
    public const FIELD_OUTPUT_CAPACITY = 'output_capacity';
    public const FIELD_V1_OUTPUT_CAPACITY = 'outputCapacity';
    public const FIELD_FEED_SIZE = 'feed_size';
    public const FIELD_V1_FEED_SIZE = 'feedSize';

    // Attributes.
    public const FIELD_INTERIOR = 'interior';
    public const FIELD_EXTERIOR = 'exterior';
    public const FIELD_OPTIONS_ATTACHMENTS = 'options_and_attachments';
    public const FIELD_V1_OPTIONS_ATTACHMENTS = 'optionsAttachments';
    public const FIELD_CHASSIS_OPTIONS = 'chassis_options';
    public const FIELD_V1_CHASSIS_OPTIONS = 'chassisOptions';
    public const FIELD_AXLE_CONFIGURATION = 'axle_configuration';
    public const FIELD_AXLE_V1_CONFIGURATION = 'axleConfiguration';
    public const FIELD_BODY_TYPE = 'body_type';
    public const FIELD_V1_BODY_TYPE = 'bodyType';
    public const FIELD_CABIN = 'cabin';
    public const FIELD_CONDITION = 'condition';
    public const FIELD_CRUSHER_TYPE = 'crusher_type';
    public const FIELD_V1_CRUSHER_TYPE = 'crusherType';
    public const FIELD_CYLINDERS = 'cylinders';
    public const FIELD_DRIVE_CONFIGURATION = 'drive_configuration';
    public const FIELD_V1_DRIVE_CONFIGURATION = 'driveConfiguration';
    public const FIELD_DRIVE = 'drive';
    public const FIELD_EMISSION_LEVEL = 'emission_level';
    public const FIELD_V1_EMISSION_LEVEL = 'emissionLevel';
    public const FIELD_FIFTH_WHEEL_TYPE = 'fifth_wheel_type';
    public const FIELD_V1_FIFTH_WHEEL_TYPE = 'fifthWheelType';
    public const FIELD_FUEL_TYPE = 'fuel_type';
    public const FIELD_V1_FUEL_TYPE = 'fuelType';
    public const FIELD_MILEAGE_UNIT = 'mileage_unit';
    public const FIELD_V1_MILEAGE_UNIT = 'mileage_unit';
    public const FIELD_NUMBER_OF_AXLES = 'number_of_axles';
    public const FIELD_V1_NUMBER_OF_AXLES = 'numberAxles';
    public const FIELD_STEERING_WHEEL_SIDE = 'steering_wheel_side';
    public const FIELD_STEERING_WHEEL_SIDE_ORIGINAL = 'steering_wheel_side_original';
    public const FIELD_V1_STEERING_WHEEL_SIDE = 'steeringWheelSide';
    public const FIELD_SUSPENSION_TYPE = 'suspension_type';
    public const FIELD_V1_SUSPENSION_TYPE = 'suspensionType';
    public const FIELD_TRACTOR_TYPE = 'tractor_type';
    public const FIELD_V1_TRACTOR_TYPE = 'tractorType';
    public const FIELD_TRANSMISSION = 'transmission';
    public const FIELD_MANUFACTURING_COUNTRY = 'manufacturing_country';
    public const FIELD_V1_MANUFACTURING_COUNTRY = 'manufacturingCountry';
    public const FIELD_GROSS_PRICE = 'gross_price';
    public const FIELD_RENT_PERIOD = 'rent_period';
    public const STEERING_WHEEL_RIGHT = 'Right Hand';
    public const STEERING_WHEEL_LEFT = 'Left Hand';
    public const CONDITION_USED = 'Used';
    public const CONDITION_NEW = 'New';

    public const ATTRIBUTES = [
        self::FIELD_INTERIOR,
        self::FIELD_EXTERIOR,
        self::FIELD_OPTIONS_ATTACHMENTS,
        self::FIELD_CHASSIS_OPTIONS,
        self::FIELD_AXLE_CONFIGURATION,
        self::FIELD_BODY_TYPE,
        self::FIELD_CABIN,
        self::FIELD_CONDITION,
        self::FIELD_CRUSHER_TYPE,
        self::FIELD_CYLINDERS,
        self::FIELD_DRIVE_CONFIGURATION,
        self::FIELD_DRIVE,
        self::FIELD_EMISSION_LEVEL,
        self::FIELD_FIFTH_WHEEL_TYPE,
        self::FIELD_FUEL_TYPE,
        self::FIELD_MILEAGE_UNIT,
        self::FIELD_NUMBER_OF_AXLES,
        self::FIELD_STEERING_WHEEL_SIDE,
        self::FIELD_SUSPENSION_TYPE,
        self::FIELD_TRACTOR_TYPE,
        self::FIELD_TRANSMISSION,
        self::FIELD_MANUFACTURING_COUNTRY,
        self::FIELD_RENT_PERIOD,
    ];

    // Statuses.
    public const STATUS_PENDING = 0;
    public const STATUS_ONLINE = 100;
    public const STATUS_OFFLINE = -10;
    public const STATUS_DELETED = -200;
    public const STATUS_MODERATED = 105;
    public const STATUS_PENDING_REVIEW = 106;
    public const STATUS_SEMI_ACTIVE = 107;
    public const STATUS_MAPPING = [
        0 => self::STATUS_OFFLINE,
        1 => self::STATUS_ONLINE,
        2 => self::STATUS_ONLINE,
        3 => self::STATUS_ONLINE,
        5 => self::STATUS_MODERATED,
        6 => self::STATUS_PENDING_REVIEW,
        7 => self::STATUS_SEMI_ACTIVE,
    ];

    public const STATUS_MODERATION = [self::STATUS_MODERATED, self::STATUS_PENDING_REVIEW];
    // The error messages for blank fields.
    public const FIELD_SELLER_BLANK_ERROR = 'The Seller must be set.';
    public const FIELD_CATEGORY_BLANK_ERROR = 'The Category must be set.';
    public const FIELD_MODEL_BLANK_ERROR = 'The Model must be set.';
    public const FIELD_MAKE_BLANK_ERROR = 'The Make must be set.';
    public const FIELD_AD_ID_BLANK_ERROR = 'The Ad_id must be set.';

    // The fields which can be directly mapped V2 => V1.
    public const SINGLE_FIELD_MAPPING = [
        self::FIELD_SELLER => self::FIELD_SELLER,
        self::FIELD_AD_ID => self::FIELD_AD_ID,
        self::FIELD_MODEL => self::FIELD_MODEL,
        self::FIELD_CATEGORY => self::FIELD_CATEGORY,
        self::FIELD_PRICE => self::FIELD_PRICE,
        self::FIELD_CURRENCY => self::FIELD_CURRENCY,
        self::FIELD_TYPE_AD => self::FIELD_V1_TYPE_AD,
    ];

    public const EXTRA_FIELDS = 'extra';

    // The extra fields which can be directly mapped V2 => V1.
    public const EXTRA_SINGLE_FIELD_MAPPING = [
        self::FIELD_PRICE_TYPE => self::FIELD_V1_PRICE_TYPE,
        self::FIELD_CONSTRUCTION_YEAR => self::FIELD_V1_CONSTRUCTION_YEAR,
        self::FIELD_HOURS_RUN => self::FIELD_V1_HOURS_RUN,
        self::FIELD_SELLERS_REFERENCE => self::FIELD_V1_SELLERS_REFERENCE,
        self::FIELD_WEIGHT => self::FIELD_WEIGHT,
        self::FIELD_VAT_RECLAIMABLE => self::FIELD_V1_VAT_RECLAIMABLE,
        self::FIELD_PRICE_EXCL_VAT => self::FIELD_V1_PRICE_EXCL_VAT,
        self::FIELD_CONSTRUCTION_MONTH => self::FIELD_V1_CONSTRUCTION_MONTH,
        self::FIELD_MILEAGE => self::FIELD_MILEAGE,
        self::FIELD_HEIGHT => self::FIELD_HEIGHT,
        self::FIELD_LENGTH => self::FIELD_LENGTH,
        self::FIELD_WIDTH => self::FIELD_WIDTH,
        self::FIELD_SERIAL => self::FIELD_SERIAL,
        self::FIELD_CONTACT_INFORMATION => self::FIELD_V1_CONTACT_INFORMATION,
        self::FIELD_BUCKET_CAPACITY => self::FIELD_V1_BUCKET_CAPACITY,
        self::FIELD_ADDITIONAL_HYDRAULICS => self::FIELD_V1_ADDITIONAL_HYDRAULICS,
        self::FIELD_OUTPUT_AUXILIARY_HYDRAULICS => self::FIELD_V1_OUTPUT_AUXILIARY_HYDRAULICS,
        self::FIELD_DIPPER_STICK_LENGTH => self::FIELD_V1_DIPPER_STICK_LENGTH,
        self::FIELD_MAX_LIFT_HEIGHT => self::FIELD_V1_MAX_LIFT_HEIGHT,
        self::FIELD_JIB_LENGTH_CRANE => self::FIELD_V1_JIB_LENGTH_CRANE,
        self::FIELD_MAX_HORIZONTAL_REACH => self::FIELD_V1_MAX_HORIZONTAL_REACH,
        self::FIELD_TANK_VOLUME => self::FIELD_V1_TANK_VOLUME,
        self::FIELD_FITS_FOLLOWING_MACHINES => self::FIELD_V1_FITS_FOLLOWING_MACHINES,
        self::FIELD_REQUIRED_HYDRAULIC_FLOW => self::FIELD_V1_REQUIRED_HYDRAULIC_FLOW,
        self::FIELD_WORKING_PRESSURE => self::FIELD_V1_WORKING_PRESSURE,
        self::FIELD_ENGINE_MODEL => self::FIELD_V1_ENGINE_MODEL,
        self::FIELD_NET_POWER => self::FIELD_V1_NET_POWER,
        self::FIELD_MAX_LIFT_CAPACITY => self::FIELD_V1_MAX_LIFT_CAPACITY,
        self::FIELD_GENERATOR_MODEL => self::FIELD_V1_GENERATOR_MODEL,
        self::FIELD_FRONT_TIRE_SIZE => self::FIELD_V1_FRONT_TIRE_SIZE,
        self::FIELD_REAR_TIRE_SIZE => self::FIELD_V1_REAR_TIRE_SIZE,
        self::FIELD_WORKING_WIDTH => self::FIELD_V1_WORKING_WIDTH,
        self::FIELD_CUTTING_WIDTH => self::FIELD_V1_CUTTING_WIDTH,
        self::FIELD_NUMBER_ROWS => self::FIELD_V1_NUMBER_ROWS,
        self::FIELD_WORKING_WIDTH_ROWS => self::FIELD_V1_WORKING_WIDTH_ROWS,
        self::FIELD_CAPACITY => self::FIELD_CAPACITY,
        self::FIELD_MAX_POWER => self::FIELD_V1_MAX_POWER,
        self::FIELD_TOP_SPEED => self::FIELD_V1_TOP_SPEED,
        self::FIELD_POWER_TAKE_OFF => self::FIELD_V1_POWER_TAKE_OFF,
        self::FIELD_CARGO_SPACE_LENGTH => self::FIELD_V1_CARGO_SPACE_LENGTH,
        self::FIELD_CARGO_SPACE_HEIGHT => self::FIELD_V1_CARGO_SPACE_HEIGHT,
        self::FIELD_CARGO_SPACE_WIDTH => self::FIELD_V1_CARGO_SPACE_WIDTH,
        self::FIELD_WHEEL_BASE => self::FIELD_V1_WHEEL_BASE,
        self::FIELD_MAX_PAYLOAD => self::FIELD_V1_MAX_PAYLOAD,
        self::FIELD_NO_DOORS => self::FIELD_V1_NO_DOORS,
        self::FIELD_NO_SEATS => self::FIELD_V1_NO_SEATS,
        self::FIELD_NO_STANDING => self::FIELD_V1_NO_STANDING,
        self::FIELD_ENGINE_CAPACITY_DISPLACEMENT => self::FIELD_V1_ENGINE_CAPACITY_DISPLACEMENT,
        self::FIELD_VOLTAGE => self::FIELD_VOLTAGE,
        self::FIELD_MAX_AXLE_WEIGHT => self::FIELD_V1_MAX_AXLE_WEIGHT,
        self::FIELD_RPM => self::FIELD_RPM,
        self::FIELD_OTHER_OPTIONS_ATTACHMENTS => self::FIELD_V1_OTHER_OPTIONS_ATTACHMENTS,
        self::FIELD_OUTPUT_CAPACITY => self::FIELD_V1_OUTPUT_CAPACITY,
        self::FIELD_FEED_SIZE => self::FIELD_V1_FEED_SIZE,
    ];

    // The extra attribute fields which can be directly mapped V2 => V1.
    public const EXTRA_ATTRIBUTE_SINGLE_VALUE_MAPPING = [
        self::FIELD_AXLE_CONFIGURATION => self::FIELD_AXLE_V1_CONFIGURATION,
        self::FIELD_BODY_TYPE => self::FIELD_V1_BODY_TYPE,
        self::FIELD_CABIN => self::FIELD_CABIN,
        self::FIELD_CONDITION => self::FIELD_CONDITION,
        self::FIELD_CRUSHER_TYPE => self::FIELD_V1_CRUSHER_TYPE,
        self::FIELD_CYLINDERS => self::FIELD_CYLINDERS,
        self::FIELD_DRIVE_CONFIGURATION => self::FIELD_V1_DRIVE_CONFIGURATION,
        self::FIELD_DRIVE => self::FIELD_DRIVE,
        self::FIELD_EMISSION_LEVEL => self::FIELD_V1_EMISSION_LEVEL,
        self::FIELD_FIFTH_WHEEL_TYPE => self::FIELD_V1_FIFTH_WHEEL_TYPE,
        self::FIELD_FUEL_TYPE => self::FIELD_V1_FUEL_TYPE,
        self::FIELD_MILEAGE_UNIT => self::FIELD_V1_MILEAGE_UNIT,
        self::FIELD_NUMBER_OF_AXLES => self::FIELD_V1_NUMBER_OF_AXLES,
        self::FIELD_STEERING_WHEEL_SIDE => self::FIELD_V1_STEERING_WHEEL_SIDE,
        self::FIELD_SUSPENSION_TYPE => self::FIELD_V1_SUSPENSION_TYPE,
        self::FIELD_TRACTOR_TYPE => self::FIELD_V1_TRACTOR_TYPE,
        self::FIELD_TRANSMISSION => self::FIELD_TRANSMISSION,
        self::FIELD_MANUFACTURING_COUNTRY => self::FIELD_V1_MANUFACTURING_COUNTRY,
    ];

    public const EXTRA_ATTRIBUTE_MULTI_VALUE_FIELD_MAPPING = [
        self::FIELD_INTERIOR => self::FIELD_INTERIOR,
        self::FIELD_EXTERIOR => self::FIELD_EXTERIOR,
        self::FIELD_OPTIONS_ATTACHMENTS => self::FIELD_V1_OPTIONS_ATTACHMENTS,
        self::FIELD_CHASSIS_OPTIONS => self::FIELD_V1_CHASSIS_OPTIONS,
    ];
}
