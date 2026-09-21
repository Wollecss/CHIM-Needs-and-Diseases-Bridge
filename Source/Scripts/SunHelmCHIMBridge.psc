Scriptname SunHelmCHIMBridge extends Quest

; ====================================================================
; --- SunHelm Globals (Player Only) ---
; ====================================================================
GlobalVariable _shHungerGlob  = None
GlobalVariable _shThirstGlob  = None
GlobalVariable _shFatigueGlob = None
GlobalVariable _shColdGlob    = None

; Cached needs stage tracking (0 to 5)
int _shLastHungerStage  = -1
int _shLastThirstStage  = -1
int _shLastFatigueStage = -1
int _shLastColdStage    = -1

; ====================================================================
; --- Diseases Tracking (Player Only) ---
; ====================================================================
bool _diseasesInitialized = false

; Food Poisoning (SunHelmSurvival.esp)
Spell _shFoodPoisonSpell = None

; Disease Spells Cache (SunHelmDiseases.esp)
; Stages: [0] = Stage 1, [1] = Stage 2 (Acute), [2] = Stage 3 (Severe)
Spell _shAtaxia3 = None
Spell _shAtaxia2 = None
Spell _shAtaxia1 = None

Spell _shBoneBreak3 = None
Spell _shBoneBreak2 = None
Spell _shBoneBreak1 = None

Spell _shRockjoint3 = None
Spell _shRockjoint2 = None
Spell _shRockjoint1 = None

Spell _shBrainRot3 = None
Spell _shBrainRot2 = None
Spell _shBrainRot1 = None

Spell _shRattles3 = None
Spell _shRattles2 = None
Spell _shRattles1 = None

Spell _shWitbane3 = None
Spell _shWitbane2 = None
Spell _shWitbane1 = None

Spell _shDampworm3 = None
Spell _shDampworm2 = None
Spell _shDampworm1 = None

Spell _shSwampFever3 = None
Spell _shSwampFever2 = None
Spell _shSwampFever1 = None

Spell _shChills3 = None
Spell _shChills2 = None
Spell _shChills1 = None

Spell _shFeebleLimb3 = None
Spell _shFeebleLimb2 = None
Spell _shFeebleLimb1 = None

Spell _shShakes3 = None
Spell _shShakes2 = None
Spell _shShakes1 = None

Spell _shWither3 = None
Spell _shWither2 = None
Spell _shWither1 = None

Spell _shDroops3 = None
Spell _shDroops2 = None
Spell _shDroops1 = None

Spell _shAstralVapors3 = None
Spell _shAstralVapors2 = None
Spell _shAstralVapors1 = None

; Water-Borne Disease Spells (SunHelmDirtyWater.esp)
Spell _shWaterChills      = None
Spell _shWaterDampworm    = None
Spell _shWaterDroops      = None
Spell _shWaterFeebleLimb  = None
Spell _shWaterShakes      = None
Spell _shWaterSwampFever  = None
Spell _shWaterWither      = None

; Cached disease state
String _lastDiseaseName  = "Initial"
int    _lastDiseaseStage = -1
float  _lastAmbientBarkTime = 0.0

; ====================================================================
; --- Lifecycle Events ---
; ====================================================================
Event OnInit()
    Debug.Notification("[SunHelm Bridge] Initialized")
    CheckSunHelmNeeds()
    CheckDiseases()
    RegisterForSingleUpdate(5.0)
    RegisterForSingleUpdateGameTime(0.1)
EndEvent

Event OnUpdate()
    CheckSunHelmNeeds()
    CheckDiseases()
    RegisterForSingleUpdate(15.0)
EndEvent

Event OnUpdateGameTime()
    CheckSunHelmNeeds()
    CheckDiseases()
    RegisterForSingleUpdateGameTime(0.25)
EndEvent

; ====================================================================
; --- Initialization ---
; ====================================================================
Function InitSunHelm()
    if (!_shHungerGlob)
        _shHungerGlob = Game.GetFormFromFile(0x00EAAE, "SunHelmSurvival.esp") as GlobalVariable
    endif
    if (!_shThirstGlob)
        _shThirstGlob = Game.GetFormFromFile(0x05C472, "SunHelmSurvival.esp") as GlobalVariable
    endif
    if (!_shFatigueGlob)
        _shFatigueGlob = Game.GetFormFromFile(0x021E3F, "SunHelmSurvival.esp") as GlobalVariable
    endif
    if (!_shColdGlob)
        _shColdGlob = Game.GetFormFromFile(0x6A13C5, "SunHelmSurvival.esp") as GlobalVariable
    endif
EndFunction

Function InitDiseases()
    if (_diseasesInitialized)
        return
    endif

    ; Food Poisoning (SunHelmSurvival.esp)
    _shFoodPoisonSpell = Game.GetFormFromFile(0x6410BF, "SunHelmSurvival.esp") as Spell

    ; Check if SunHelmDiseases.esp is active
    if (Game.GetFormFromFile(0x00084C, "SunHelmDiseases.esp"))
        ; Ataxia
        _shAtaxia3 = Game.GetFormFromFile(0x00084D, "SunHelmDiseases.esp") as Spell
        _shAtaxia2 = Game.GetFormFromFile(0x00084C, "SunHelmDiseases.esp") as Spell
        _shAtaxia1 = Game.GetFormFromFile(0x0B877C, "Skyrim.esm") as Spell

        ; Bone Break Fever
        _shBoneBreak3 = Game.GetFormFromFile(0x00084F, "SunHelmDiseases.esp") as Spell
        _shBoneBreak2 = Game.GetFormFromFile(0x00084E, "SunHelmDiseases.esp") as Spell
        _shBoneBreak1 = Game.GetFormFromFile(0x0B877E, "Skyrim.esm") as Spell

        ; Rockjoint
        _shRockjoint3 = Game.GetFormFromFile(0x000857, "SunHelmDiseases.esp") as Spell
        _shRockjoint2 = Game.GetFormFromFile(0x000856, "SunHelmDiseases.esp") as Spell
        _shRockjoint1 = Game.GetFormFromFile(0x0B8782, "Skyrim.esm") as Spell

        ; Brain Rot
        _shBrainRot3 = Game.GetFormFromFile(0x000851, "SunHelmDiseases.esp") as Spell
        _shBrainRot2 = Game.GetFormFromFile(0x000850, "SunHelmDiseases.esp") as Spell
        _shBrainRot1 = Game.GetFormFromFile(0x0B877F, "Skyrim.esm") as Spell

        ; Rattles
        _shRattles3 = Game.GetFormFromFile(0x000855, "SunHelmDiseases.esp") as Spell
        _shRattles2 = Game.GetFormFromFile(0x000854, "SunHelmDiseases.esp") as Spell
        _shRattles1 = Game.GetFormFromFile(0x0B8781, "Skyrim.esm") as Spell

        ; Witbane
        _shWitbane3 = Game.GetFormFromFile(0x000859, "SunHelmDiseases.esp") as Spell
        _shWitbane2 = Game.GetFormFromFile(0x000858, "SunHelmDiseases.esp") as Spell
        _shWitbane1 = Game.GetFormFromFile(0x0B8783, "Skyrim.esm") as Spell

        ; Dampworm
        _shDampworm3 = Game.GetFormFromFile(0x000846, "SunHelmDiseases.esp") as Spell
        _shDampworm2 = Game.GetFormFromFile(0x000845, "SunHelmDiseases.esp") as Spell
        _shDampworm1 = Game.GetFormFromFile(0x00083A, "SunHelmDiseases.esp") as Spell

        ; Swamp Fever
        _shSwampFever3 = Game.GetFormFromFile(0x000842, "SunHelmDiseases.esp") as Spell
        _shSwampFever2 = Game.GetFormFromFile(0x000841, "SunHelmDiseases.esp") as Spell
        _shSwampFever1 = Game.GetFormFromFile(0x000840, "SunHelmDiseases.esp") as Spell

        ; Chills
        _shChills3 = Game.GetFormFromFile(0x00083B, "SunHelmDiseases.esp") as Spell
        _shChills2 = Game.GetFormFromFile(0x00083C, "SunHelmDiseases.esp") as Spell
        _shChills1 = Game.GetFormFromFile(0x00083D, "SunHelmDiseases.esp") as Spell

        ; Feeble Limb
        _shFeebleLimb3 = Game.GetFormFromFile(0x000848, "SunHelmDiseases.esp") as Spell
        _shFeebleLimb2 = Game.GetFormFromFile(0x000847, "SunHelmDiseases.esp") as Spell
        _shFeebleLimb1 = Game.GetFormFromFile(0x000837, "SunHelmDiseases.esp") as Spell

        ; Shakes
        _shShakes3 = Game.GetFormFromFile(0x00083F, "SunHelmDiseases.esp") as Spell
        _shShakes2 = Game.GetFormFromFile(0x00083E, "SunHelmDiseases.esp") as Spell
        _shShakes1 = Game.GetFormFromFile(0x000838, "SunHelmDiseases.esp") as Spell

        ; Wither
        _shWither3 = Game.GetFormFromFile(0x000844, "SunHelmDiseases.esp") as Spell
        _shWither2 = Game.GetFormFromFile(0x000843, "SunHelmDiseases.esp") as Spell
        _shWither1 = Game.GetFormFromFile(0x000839, "SunHelmDiseases.esp") as Spell

        ; Droops
        _shDroops3 = Game.GetFormFromFile(0x000853, "SunHelmDiseases.esp") as Spell
        _shDroops2 = Game.GetFormFromFile(0x000852, "SunHelmDiseases.esp") as Spell
        _shDroops1 = Game.GetFormFromFile(0x0285C1, "Dragonborn.esm") as Spell

        ; Astral Vapors
        _shAstralVapors3 = Game.GetFormFromFile(0x00084B, "SunHelmDiseases.esp") as Spell
        _shAstralVapors2 = Game.GetFormFromFile(0x00084A, "SunHelmDiseases.esp") as Spell
        _shAstralVapors1 = Game.GetFormFromFile(0x000849, "SunHelmDiseases.esp") as Spell
    else
        ; Fallback to vanilla diseases if SunHelmDiseases.esp is not loaded
        _shAtaxia1    = Game.GetFormFromFile(0x0B877C, "Skyrim.esm") as Spell
        _shBoneBreak1 = Game.GetFormFromFile(0x0B877E, "Skyrim.esm") as Spell
        _shRockjoint1 = Game.GetFormFromFile(0x0B8782, "Skyrim.esm") as Spell
        _shBrainRot1  = Game.GetFormFromFile(0x0B877F, "Skyrim.esm") as Spell
        _shRattles1   = Game.GetFormFromFile(0x0B8781, "Skyrim.esm") as Spell
        _shWitbane1   = Game.GetFormFromFile(0x0B8783, "Skyrim.esm") as Spell
    endif

    ; Check if SunHelmDirtyWater.esp is active (Water-Borne Diseases)
    if (Game.GetFormFromFile(0x000812, "SunHelmDirtyWater.esp"))
        _shWaterChills      = Game.GetFormFromFile(0x000812, "SunHelmDirtyWater.esp") as Spell
        _shWaterDampworm    = Game.GetFormFromFile(0x000813, "SunHelmDirtyWater.esp") as Spell
        _shWaterDroops      = Game.GetFormFromFile(0x000814, "SunHelmDirtyWater.esp") as Spell
        _shWaterFeebleLimb  = Game.GetFormFromFile(0x000815, "SunHelmDirtyWater.esp") as Spell
        _shWaterShakes      = Game.GetFormFromFile(0x000816, "SunHelmDirtyWater.esp") as Spell
        _shWaterSwampFever  = Game.GetFormFromFile(0x000817, "SunHelmDirtyWater.esp") as Spell
        _shWaterWither      = Game.GetFormFromFile(0x000818, "SunHelmDirtyWater.esp") as Spell
    endif

    _diseasesInitialized = true
EndFunction

; ====================================================================
; --- Needs Stage Calculation ---
; ====================================================================
int Function GetHungerStage(int val)
    if (val < 40)
        return 0 ; Well Fed
    elseif (val < 80)
        return 1 ; Satisfied
    elseif (val < 140)
        return 2 ; Peckish
    elseif (val < 240)
        return 3 ; Hungry
    elseif (val < 360)
        return 4 ; Ravenous
    else
        return 5 ; Starving
    endif
EndFunction

int Function GetThirstStage(int val)
    if (val < 40)
        return 0 ; Quenched
    elseif (val < 60)
        return 1 ; Sated
    elseif (val < 120)
        return 2 ; Thirsty
    elseif (val < 180)
        return 3 ; Parched
    elseif (val < 300)
        return 4 ; Dehydrated
    else
        return 5 ; Severely Dehydrated
    endif
EndFunction

int Function GetFatigueStage(int val)
    if (val < 80)
        return 0 ; Well Rested
    elseif (val < 160)
        return 1 ; Rested
    elseif (val < 240)
        return 2 ; Slightly Tired
    elseif (val < 360)
        return 3 ; Tired
    elseif (val < 480)
        return 4 ; Weary
    else
        return 5 ; Exhausted
    endif
EndFunction

int Function GetColdStage(int val)
    if (val < 25)
        return 0 ; Warm
    elseif (val < 150)
        return 1 ; Comfortable
    elseif (val < 250)
        return 2 ; Chilly
    elseif (val < 450)
        return 3 ; Cold
    elseif (val < 700)
        return 4 ; Freezing
    else
        return 5 ; Frigid
    endif
EndFunction

; ====================================================================
; --- Needs Evaluation ---
; ====================================================================
Function CheckSunHelmNeeds()
    if (!_shHungerGlob || !_shThirstGlob || !_shFatigueGlob || !_shColdGlob)
        InitSunHelm()
        if (!_shHungerGlob)
            return
        endif
    endif

    int curHungerVal  = _shHungerGlob.GetValueInt()
    int curThirstVal  = _shThirstGlob.GetValueInt()
    int curFatigueVal = _shFatigueGlob.GetValueInt()
    int curColdVal    = _shColdGlob.GetValueInt()

    int curHungerStage  = GetHungerStage(curHungerVal)
    int curThirstStage  = GetThirstStage(curThirstVal)
    int curFatigueStage = GetFatigueStage(curFatigueVal)
    int curColdStage    = GetColdStage(curColdVal)

    bool stageChanged = (curHungerStage != _shLastHungerStage) || (curThirstStage != _shLastThirstStage) || (curFatigueStage != _shLastFatigueStage) || (curColdStage != _shLastColdStage)

    if (stageChanged)
        String payload = "sunhelm_needs@" + curHungerStage + "@" + curThirstStage + "@" + curFatigueStage + "@" + curColdStage
        Debug.Notification("[SunHelm Bridge] Needs updated")
        AIAgentFunctions.logMessage(payload, "status_msg")

        _shLastHungerStage  = curHungerStage
        _shLastThirstStage  = curThirstStage
        _shLastFatigueStage = curFatigueStage
        _shLastColdStage    = curColdStage
    endif
EndFunction

; ====================================================================
; --- Diseases Evaluation ---
; ====================================================================
Function CheckDiseases()
    if (!_diseasesInitialized)
        InitDiseases()
    endif

    Actor player = Game.GetPlayer()
    if (!player)
        return
    endif

    String dName = "None"
    int    dStage = 0
    String dCues = "none"

    ; 1. Check Food Poisoning first
    if (_shFoodPoisonSpell && player.HasSpell(_shFoodPoisonSpell))
        dName  = "Food Poisoning"
        dStage = 1
        dCues  = "stomach_cramps"
    ; 2. Rockjoint
    elseif (_shRockjoint3 && player.HasSpell(_shRockjoint3))
        dName  = "Rockjoint"
        dStage = 3
        dCues  = "locked_joints,purple_veins"
    elseif (_shRockjoint2 && player.HasSpell(_shRockjoint2))
        dName  = "Rockjoint"
        dStage = 2
        dCues  = "locked_joints,purple_veins"
    elseif (_shRockjoint1 && player.HasSpell(_shRockjoint1))
        dName  = "Rockjoint"
        dStage = 1
        dCues  = "locked_joints"
    ; 3. Bone Break Fever
    elseif (_shBoneBreak3 && player.HasSpell(_shBoneBreak3))
        dName  = "Bone Break Fever"
        dStage = 3
        dCues  = "blotches,chills_shiver"
    elseif (_shBoneBreak2 && player.HasSpell(_shBoneBreak2))
        dName  = "Bone Break Fever"
        dStage = 2
        dCues  = "blotches,chills_shiver"
    elseif (_shBoneBreak1 && player.HasSpell(_shBoneBreak1))
        dName  = "Bone Break Fever"
        dStage = 1
        dCues  = "blotches"
    ; 4. Ataxia
    elseif (_shAtaxia3 && player.HasSpell(_shAtaxia3))
        dName  = "Ataxia"
        dStage = 3
        dCues  = "limp_arm"
    elseif (_shAtaxia2 && player.HasSpell(_shAtaxia2))
        dName  = "Ataxia"
        dStage = 2
        dCues  = "limp_arm"
    elseif (_shAtaxia1 && player.HasSpell(_shAtaxia1))
        dName  = "Ataxia"
        dStage = 1
        dCues  = "limp_arm"
    ; 5. Brain Rot
    elseif (_shBrainRot3 && player.HasSpell(_shBrainRot3))
        dName  = "Brain Rot"
        dStage = 3
        dCues  = "headache,necrotic_skin"
    elseif (_shBrainRot2 && player.HasSpell(_shBrainRot2))
        dName  = "Brain Rot"
        dStage = 2
        dCues  = "headache,necrotic_skin"
    elseif (_shBrainRot1 && player.HasSpell(_shBrainRot1))
        dName  = "Brain Rot"
        dStage = 1
        dCues  = "headache"
    ; 6. Rattles
    elseif (_shRattles3 && player.HasSpell(_shRattles3))
        dName  = "Rattles"
        dStage = 3
        dCues  = "violent_cough,blotches"
    elseif (_shRattles2 && player.HasSpell(_shRattles2))
        dName  = "Rattles"
        dStage = 2
        dCues  = "violent_cough,blotches"
    elseif (_shRattles1 && player.HasSpell(_shRattles1))
        dName  = "Rattles"
        dStage = 1
        dCues  = "violent_cough"
    ; 7. Witbane
    elseif (_shWitbane3 && player.HasSpell(_shWitbane3))
        dName  = "Witbane"
        dStage = 3
        dCues  = "headache,blotches"
    elseif (_shWitbane2 && player.HasSpell(_shWitbane2))
        dName  = "Witbane"
        dStage = 2
        dCues  = "headache,blotches"
    elseif (_shWitbane1 && player.HasSpell(_shWitbane1))
        dName  = "Witbane"
        dStage = 1
        dCues  = "headache"
    ; 8. Dampworm
    elseif (_shDampworm3 && player.HasSpell(_shDampworm3))
        dName  = "Dampworm"
        dStage = 3
        dCues  = "boils"
    elseif (_shDampworm2 && player.HasSpell(_shDampworm2))
        dName  = "Dampworm"
        dStage = 2
        dCues  = "boils"
    elseif (_shWaterDampworm && player.HasSpell(_shWaterDampworm))
        dName  = "Dampworm"
        dStage = 1
        dCues  = "boils,waterborne"
    elseif (_shDampworm1 && player.HasSpell(_shDampworm1))
        dName  = "Dampworm"
        dStage = 1
        dCues  = "boils"
    ; 9. Swamp Fever
    elseif (_shSwampFever3 && player.HasSpell(_shSwampFever3))
        dName  = "Swamp Fever"
        dStage = 3
        dCues  = "boils,chills_shiver"
    elseif (_shSwampFever2 && player.HasSpell(_shSwampFever2))
        dName  = "Swamp Fever"
        dStage = 2
        dCues  = "boils,chills_shiver"
    elseif (_shWaterSwampFever && player.HasSpell(_shWaterSwampFever))
        dName  = "Swamp Fever"
        dStage = 1
        dCues  = "boils,waterborne"
    elseif (_shSwampFever1 && player.HasSpell(_shSwampFever1))
        dName  = "Swamp Fever"
        dStage = 1
        dCues  = "boils"
    ; 10. Chills
    elseif (_shChills3 && player.HasSpell(_shChills3))
        dName  = "Chills"
        dStage = 3
        dCues  = "chills_shiver,purple_veins"
    elseif (_shChills2 && player.HasSpell(_shChills2))
        dName  = "Chills"
        dStage = 2
        dCues  = "chills_shiver,purple_veins"
    elseif (_shWaterChills && player.HasSpell(_shWaterChills))
        dName  = "Chills"
        dStage = 1
        dCues  = "chills_shiver,waterborne"
    elseif (_shChills1 && player.HasSpell(_shChills1))
        dName  = "Chills"
        dStage = 1
        dCues  = "chills_shiver"
    ; 11. Feeble Limb
    elseif (_shFeebleLimb3 && player.HasSpell(_shFeebleLimb3))
        dName  = "Feeble Limb"
        dStage = 3
        dCues  = "necrotic_skin"
    elseif (_shFeebleLimb2 && player.HasSpell(_shFeebleLimb2))
        dName  = "Feeble Limb"
        dStage = 2
        dCues  = "necrotic_skin"
    elseif (_shWaterFeebleLimb && player.HasSpell(_shWaterFeebleLimb))
        dName  = "Feeble Limb"
        dStage = 1
        dCues  = "necrotic_skin,waterborne"
    elseif (_shFeebleLimb1 && player.HasSpell(_shFeebleLimb1))
        dName  = "Feeble Limb"
        dStage = 1
        dCues  = "necrotic_skin"
    ; 12. Shakes
    elseif (_shShakes3 && player.HasSpell(_shShakes3))
        dName  = "Shakes"
        dStage = 3
        dCues  = "tremors,blotches"
    elseif (_shShakes2 && player.HasSpell(_shShakes2))
        dName  = "Shakes"
        dStage = 2
        dCues  = "tremors,blotches"
    elseif (_shWaterShakes && player.HasSpell(_shWaterShakes))
        dName  = "Shakes"
        dStage = 1
        dCues  = "tremors,waterborne"
    elseif (_shShakes1 && player.HasSpell(_shShakes1))
        dName  = "Shakes"
        dStage = 1
        dCues  = "tremors"
    ; 13. Wither
    elseif (_shWither3 && player.HasSpell(_shWither3))
        dName  = "Wither"
        dStage = 3
        dCues  = "blotches"
    elseif (_shWither2 && player.HasSpell(_shWither2))
        dName  = "Wither"
        dStage = 2
        dCues  = "blotches"
    elseif (_shWaterWither && player.HasSpell(_shWaterWither))
        dName  = "Wither"
        dStage = 1
        dCues  = "blotches,waterborne"
    elseif (_shWither1 && player.HasSpell(_shWither1))
        dName  = "Wither"
        dStage = 1
        dCues  = "blotches"
    ; 14. Droops
    elseif (_shDroops3 && player.HasSpell(_shDroops3))
        dName  = "Droops"
        dStage = 3
        dCues  = "limp_arm"
    elseif (_shDroops2 && player.HasSpell(_shDroops2))
        dName  = "Droops"
        dStage = 2
        dCues  = "limp_arm"
    elseif (_shWaterDroops && player.HasSpell(_shWaterDroops))
        dName  = "Droops"
        dStage = 1
        dCues  = "limp_arm,waterborne"
    elseif (_shDroops1 && player.HasSpell(_shDroops1))
        dName  = "Droops"
        dStage = 1
        dCues  = "limp_arm"
    ; 15. Astral Vapors
    elseif (_shAstralVapors3 && player.HasSpell(_shAstralVapors3))
        dName  = "Astral Vapors"
        dStage = 3
        dCues  = "purple_veins,chills_shiver"
    elseif (_shAstralVapors2 && player.HasSpell(_shAstralVapors2))
        dName  = "Astral Vapors"
        dStage = 2
        dCues  = "purple_veins,chills_shiver"
    elseif (_shAstralVapors1 && player.HasSpell(_shAstralVapors1))
        dName  = "Astral Vapors"
        dStage = 1
        dCues  = "purple_veins"
    endif

    ; Check if disease state changed
    bool diseaseChanged = (dName != _lastDiseaseName) || (dStage != _lastDiseaseStage)
    if (diseaseChanged)
        String payload = "sunhelm_disease@" + dName + "@" + dStage + "@" + dCues
        Debug.Notification("[SunHelm Bridge] Disease: " + dName + " (Stage " + dStage + ")")
        AIAgentFunctions.logMessage(payload, "status_msg")

        _lastDiseaseName  = dName
        _lastDiseaseStage = dStage
    endif

    ; Path B: Ambient Proximity Barks for Stage 2 / 3
    if (dStage >= 2)
        float now = Utility.GetCurrentRealTime()
        if ((now - _lastAmbientBarkTime) >= 180.0)
            Actor nearbyAgent = AIAgentFunctions.getClosestAgent()
            if (nearbyAgent && nearbyAgent != player && !nearbyAgent.IsDead() && !nearbyAgent.IsInCombat())
                String npcName = nearbyAgent.GetActorBase().GetName()
                String barkPrompt = "You notice the player walking near you looking severely afflicted with " + dName + " (" + dCues + "). React aloud in one short sentence reflecting your personality."
                AIAgentFunctions.requestMessageForEligibleActor(barkPrompt, "chat", npcName)
                _lastAmbientBarkTime = now
            endif
        endif
    endif
EndFunction
