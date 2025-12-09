const FRANCHISEE_APPLICATION_STEP = {
    BasicDetails: 'basic-details',
    FranchiseInfo: 'franchisee-info',
    Requirements: 'requirements',
    Finished: 'finished',
};

const FRANCHISEE_STATUS = {
    Active: 1,
    Inactive: 2,
};

const CREATE_STORE_STEP = {
    BasicDetails: 'basic-details',
    ContactInfo: 'contact-info',
    Specifications: 'specifications',
    StoreRequirements: 'store-requirements',
    Finished: 'finished',
};

const STORE_RATING_STEP = {
    AuthorizedProducts: 'authorized-products',
    CleanlinessSanitationMaintenance: 'cleanliness-sanitation-maintenance',
    ProductionQuality: 'production-quality',
    OperationalExcellenceFoodSafety: 'operational-excellence-food-safety',
    CustomerExperience: 'customer-experience',
    Finished: 'finished',
};

const STORE_STATUS = {
    Open: 'Open',
    Future: 'Future',
    TemporaryClosed: 'TemporaryClosed',
    Closed: 'Closed',
    Deactivated: 'Deactivated',
};

const STORE_TYPE = {
    Branch: 'Branch',
    Express: 'Express',
    Junior: 'Junior',
    Outlet: 'Outlet',
};

const STORE_GROUP = {
    CompanyOwnedJFC: 'CompanyOwnedJFC',
    CompanyOwnedBGC: 'CompanyOwnedBGC',
    FranchiseeFZE: 'FranchiseeFZE',
};

const STORE_INSURANCE_TYPE = {
    CGL: 'CGL',
    Fire: 'Fire',
    GPA: 'GPA',
};

const STORE_WAREHOUSE = {
    PAM: 'PAM',
    PSG: 'PSG',
    MDE: 'MDE',
    DVO: 'DVO',
    CGY: 'CGY',
    TAC: 'TAC',
    DPL: 'DPL',
    CAR: 'CAR',
};

const QUESTIONNAIRE_ANSWER = {
    Yes: 'Yes',
    No: 'No',
    NotApplicable: 'Not Applicable',
};

export {
    CREATE_STORE_STEP,
    FRANCHISEE_APPLICATION_STEP,
    FRANCHISEE_STATUS,
    STORE_GROUP,
    STORE_INSURANCE_TYPE,
    STORE_RATING_STEP,
    STORE_STATUS,
    STORE_TYPE,
    STORE_WAREHOUSE,
    QUESTIONNAIRE_ANSWER,
};
