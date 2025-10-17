/* 
 * modifed: igorpauk 2017-18
 */

#include "common.h"
#include "srv.h"
#include "fight.h"
#include "io.h"
#include "persmagic.h"
#include "luaif.h"
#include "pers.h"


fs_skillVal_t  fs_persBaseExp[MAX_LEVEL+1] = { // base experience
		0,
		10,
		15,
		25,
		35,
		40,
		60,
		85,
		100,
		125,
		170,
		205,
		230,
		295,
		360,
		460,
		590,
		760,
		970,
		1250,
		1600
};
fs_skillVal_t  fs_persSkillSum[MAX_LEVEL+1][2] = { // average summs { BASE_SUM, ITEM_SUM }
	{    0,    0 },
	{   30,  100 },
	{   36,  120 },
	{   43,  144 },
	{   52,  173 },
	{   62,  207 },
	{   75,  249 },
	{   90,  299 },
	{  107,  358 },
	{  129,  430 },
	{  155,  516 },
	{  186,  722 },
	{  186,  795 },
	{  186,  874 },
	{  186,  961 },
	{  186, 1058 },
	{  186, 1163 },
	{  186, 1280 },
	{  186, 1408 },
	{  186, 1548 },
	{  186, 1703 }
};
fs_skillVal_t  fs_persBaseHonor[MAX_LEVEL+1] = { // base honor
		0, 		//0
		400, 	//1
		400, 	//2
		400, 	//3
		500, 	//4
		640, 	//5
		800, 	//6
		1000,	//7
		1280,	//8
		1600,	//9
		2000,	//10
		2560,	//11
		3200,	//12
		4000,	//13
		5120,	//14
		6400,	//15
		7200,	//16
		8400,	//17
		9600,	//18
		10000,	//19
		12000,	//20
};


fs_pers_t *fs_persCreate(int id) {
	fs_pers_t    *pers;
	static int   persId = 0;
	int          rsc;

	pers = malloc(sizeof(fs_pers_t));
	if (!pers) {
		WARN("malloc() failed");
		return NULL;
	}
	if (id <= 0) id = ++persId;
	persId = MAX(persId,id);
	memset(pers,0,sizeof(fs_pers_t));
	pers->id = id;
	pers->ctime = fs_stime;
	pers->aetime = fs_stime;
	pers->_inicHod = false;
	pers->effVec = v_init(NULL);
	pers->cmbVec = v_init(NULL);
	pers->honorDataVec = v_init(NULL);
	pers->followPers = v_init(NULL);
	for (rsc=0; rsc<=_RSC_MAXCODE; rsc++) {	// random seed init
		pers->_rs[rsc] = (unsigned)(pers->id*pers->ctime*(rsc+1)*rand());
	}
	return pers;
}

errno_t fs_persDelete(fs_pers_t *pers) {
	fs_persEff_t *eff;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	free(pers->nick);
	free(pers->nickData);
	while ((eff = v_pop(pers->effVec))) fs_persEffDelete(eff);
	v_freeData(pers->cmbVec);
	v_freeData(pers->honorDataVec);
	v_freeData(pers->followPers);
	v_free(pers->effVec);
	v_free(pers->cmbVec);
	v_free(pers->honorDataVec);
	v_free(pers->followPers);
	if (pers->client) fs_clientDisconnect(pers->client);
	free(pers->ctrlFile);
	free(pers->ctrlFunc);
	free(pers->PetSrc);
	free(pers->nParts);
	free(pers);
	return OK;
}

fs_pers_t *fs_persGetById(int id) {
	fs_pers_t    *pers;
	viter_t      vi;

	v_reset(fs_persVec,&vi);
	while ((pers = v_each(fs_persVec,&vi))) {
		if (pers->id == id) break;
	}
	return pers;
}

fs_persEff_t *fs_persEffCreate(int id) {
	fs_persEff_t      *eff;

	eff = malloc(sizeof(fs_persEff_t));
	if (!eff) {
		WARN("malloc() failed");
		return NULL;
	}
	memset(eff,0,sizeof(fs_persEff_t));
	eff->id = id;
	return eff;
}

errno_t fs_persEffDelete(fs_persEff_t *eff) {
	if (!eff) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	free(eff->animData);
	free(eff->title);
	free(eff->picture);
	free(eff);
	return OK;
}

fs_persEff_t *fs_persEffCopy(fs_persEff_t *eff) {
	fs_persEff_t      *effCopy;

	if (!eff) {
		WARN("Invalid arguments");
		return NULL;
	}
	effCopy = fs_persEffCreate(eff->id);
	if (!effCopy) return NULL;
	memcpy(effCopy,eff,sizeof(fs_persEff_t));
	if (eff->title) effCopy->title = strdup(eff->title);
	if (eff->picture) effCopy->picture = strdup(eff->picture);
	if (eff->animData) effCopy->animData = strdup(eff->animData);
	return effCopy;
}

// Event packet:
// #0  cmd          (type INT, val=FS_SC_NONE)
// #1  event        (type INT/NINT, val IN fs_persEvent_t)
// #n  ...
errno_t fs_persSetEvent(fs_pers_t *pers, fs_persEvent_t event, char *fmt, ...) {
	va_list      ap;
	va_list      ap2;
	fs_packet_t  *packet;
	fs_param_t   *param;
	
	fs_followPers_t   	*followPersData;

	if (!pers || !event) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (!pers->client || (pers->flags & FS_PF_BOT)) return OK;
	if ((pers->flags & FS_PF_FIGHTTEST) && (event != FS_PE_SRVSHUTDOWN) && (event != FS_PE_ATTACKNOW) && (event != FS_PE_ATTACK) && (event != FS_PE_FIGHTOVER)) return OK;
	if (event == FS_PE_DAMAGE) {
		if (pers->_dmgEventCnt > 10) return OK;
		pers->_dmgEventCnt++;
	}
	
	if(pers->fight->status != FS_FS_RUNNING && event == FS_PE_FIGHTSTATE){
		return OK;
	}
	
	va_start(ap,fmt);
	packet = fs_packetCreate();
	PARAM_NEW(param);
	PARAM_SETINT(param,FS_SC_NONE);
	PARAM_PUSH(packet,param);
	PARAM_NEW(param);
	PARAM_SETINT(param,event);
	PARAM_PUSH(packet,param);
	if (fmt) fs_addParamsVA(&(packet->params),fmt,ap);
	v_push(pers->client->outPacketVec,packet);
	
	DEBUG("EVENT: ==> sock: %d, persId=%d, event=%d",pers->client->sock,pers->id,event);
	fs_debugParams(&(packet->params),"EVENT: OUT",true);
	va_end(ap);
	
	bool equal = false;
	switch(event){
		case FS_PE_OPPNEW:
		case FS_PE_ATTACK:
		case FS_PE_DEATH:
		case FS_PE_DAMAGE:
			equal = true;
			break;
	}
	
	if(!equal) return OK;
	
	viter_t      vi;
	
	if(pers->followPers->size <= 0){
		return OK;
	}
	
	switch(event){
		case FS_PE_OPPNEW:
		//case FS_PE_EFFECTUSE:
		//case FS_PE_EFFECTAPPLY:
		//case FS_PE_FIGHTSTATE:
		//case FS_PE_OPPWAIT:
		case FS_PE_ATTACK:
		//case FS_PE_ATTACKNOW:
		//case FS_PE_ATTACKWAIT:
		case FS_PE_DEATH:
		case FS_PE_DAMAGE:
		//case FS_PE_FIGHTOVER:
		//case FS_PE_MYFIGHTRETURN:
		//case FS_PE_FIGHTOVER:
			//Check Followers And See Him
			//fs_pers_t			*persFollow;
			v_reset(pers->followPers,&vi);
			int fidx=0;
			while ((followPersData = v_each(pers->followPers,&vi))) {
				//check if not follower
				if(!followPersData){
					continue;
				}
				if(followPersData->pers){
					if(!followPersData->pers->client) continue;
					//if(followPersData->pers->client->flags & FS_CF_DISCONN) continue; //DISCONNECT POSHLI DALEE
					
					//DEBUGXXX("EVENT_FOLLOWER %d: ==> %d",pers->id,followPersData->pers->id);
					
					if (!followPersData->pers || !event) {
						continue;
					}
					if (!followPersData->pers->client || (followPersData->pers->flags & FS_PF_BOT)) continue;
					if (followPersData->pers->client->flags & FS_CF_DISCONN) continue;

					if (event == FS_PE_DAMAGE) {
						if (followPersData->pers->_dmgEventCnt > 10) continue;
						followPersData->pers->_dmgEventCnt++;
					}
					va_start(ap2,fmt);
					packet = fs_packetCreate();
					PARAM_NEW(param);
					PARAM_SETINT(param,FS_SC_NONE);
					PARAM_PUSH(packet,param);
					PARAM_NEW(param);
					PARAM_SETINT(param,event);
					PARAM_PUSH(packet,param);
					if (fmt) fs_addParamsVA(&(packet->params),fmt,ap2);
					v_push(followPersData->pers->client->outPacketVec,packet);
					
					//DEBUGXXX("EVENTSENDPACKFOLLOW: ==> sock: %d, persId=%d, event=%d",followPersData->pers->client->sock,followPersData->pers->id,event);
					fs_debugParams(&(packet->params),"EVENT: OUT",true);
					va_end(ap2);

				}
				fidx++;
			}
			break;
	}
	
	return OK;
}

// ================================================================================== //

errno_t fs_persDebugSkills(fs_pers_t *pers) {
	fs_skill_t   skill;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	DEBUG("persId: %d ========= SKILLS ==========",pers->id);
	for (skill=0; skill<=FS_SK_MAXCODE; skill++) {
		DEBUG("%#2d --> %-d",skill,PERS_SKILL(pers,skill));
	}
	DEBUG("==========================================");
	return OK;
}

// part: 1 - head, 2 - body, 3 - legs
// wpnEff: 0 - off, 1 - on
errno_t fs_persAttack(fs_pers_t *pers, int part, bool wpnEff) {
	fs_pers_t         *opp;
	fs_persEff_t      *eff, *auraEff;
	fs_persCmb_t      *cmb;
	double            avgBS, avgIS, evP1, evP2, pE, pC, pB, pAE, pAC, pAB, Ap;
	double            dmgMin, dmgMax, dmg, dmgA, saveDMG, cmbP, ndmg, dmgDxPlus;
	bool              fE, fC, fB;
	int               kick, rnd, dmgDxPlusInt;
	double			  dmgAura, dmgAuraAoe, bk1, bk2;
	char              *animData;
	fs_persCharge_t   charge;
	vector_t          v1, v2; //MultiAttack Personages
	viter_t           vi; //MultiAttack Personages
	fs_pers_t         *p; //MultiAttack Personages
	fs_pers_t		  *target; //MultiAttack Personages
	int				  idx, i, aoeWeight, aoeRand;
	int _chargeVampInt = 0;
	float _chargeVamp = 0;

	int               cxMagDefInt = 0, cxMagDmgInt = 0;
	double            cxMagDef = 0, cxMagDmg = 0, cxMagAdd = 0;

	
	//DEBUGXXX("BOTATTACK1");
	
	if (!pers || (part < 1) || (part > 6)) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	
	//DEBUGXXX("BOTATTACK2");
	
	opp = pers->opponent;
	if (!opp || (pers->status != FS_PS_ACTIVE)) return ERR_WRONG_STATE;
	
	//DEBUGXXX("BOTATTACK3");
	
	if (wpnEff) {	// looking for a weapon effect available
		v_reset(pers->effVec,0);
		while ((eff = v_each(pers->effVec,0))) {
			if (eff->flags & (FS_PEF_ACTIVE | FS_PEF_AUX)) continue;
			if (eff->flags & FS_PEF_WEAPONEFFECT) break;
		}
		if (eff) {
			eff->flags &= ~FS_PEF_PASSTURN;
			if (fs_persUseEffect(pers,eff,pers,NULL) != OK) wpnEff = 0;
		} else wpnEff = 0;
	}

	avgBS = (BASE_SUM(PERS_LEVEL(pers)) + BASE_SUM(PERS_LEVEL(opp))) / 2;
	avgIS = (ITEM_SUM(PERS_LEVEL(pers)) + ITEM_SUM(PERS_LEVEL(opp))) / 2;
	avgIS = MAX((fs_persGetCost2(pers) + fs_persGetCost2(opp))/2 - avgBS , avgIS*0.6);

	if (!avgBS || !avgIS) {
		WARN("Wrong data for given personage levels: avgBS=%.2f, avgIS=%.2f, lvl1=%d, lvl2=%d",avgBS,avgIS,PERS_LEVEL(pers),PERS_LEVEL(opp));
		return ERR_CONFIG;
	}

	//DEBUGXXX("BOTATTACK4");
	
	// event limits
	evP1 = pers->art ? _evPA: _evP0;
	evP2 = opp->art ? _evPA: _evP0;

	// evade, crit, block probability
	pE = MIN(MAX((PERS_SKILL(opp,FS_SK_DEX) - (avgBS/5)) * (_pE-_pE0) / (avgIS/2), 0) + _pE0, evP2);
	pC = MIN(MAX((PERS_SKILL(pers,FS_SK_INT) - (avgBS/5)) * (_pC-_pC0) / (avgIS/2), 0) + _pC0, evP1);
	pB = MIN(MAX((PERS_SKILL(opp,FS_SK_ENDUR) - (avgBS/5)) * (_pB-_pB0) / (avgIS/2), 0) + _pB0, evP2);

	// anti-evade, anti-crit, anti-block probability
	pAE = MIN(MAX((PERS_SKILL(pers,FS_SK_ENDUR) - (avgBS/5)) * (_pAE-_pAE0) / (avgIS/2), 0) + _pAE0, evP1);
	pAC = opp->art ? MIN(MAX((PERS_SKILL(opp,FS_SK_DEX) - (avgBS/5)) * (_pAC-_pAC0) / (avgIS/2), 0) + _pAC0, evP2): 0;
	pAB = pers->art ? MIN(MAX((PERS_SKILL(pers,FS_SK_INT) - (avgBS/5)) * (_pAB-_pAB0) / (avgIS/2), 0) + _pAB0, evP1): 0;

	// damage
	dmgMin = PERS_SKILL(pers,FS_SK_PWRMIN)*0.1 + (PERS_SKILL(pers,FS_SK_STR) + _COMP(MIN(PERS_LEVEL(pers),10),_X0)*(1 - _IComp)) * _Xs * 0.8;
	dmgMax = PERS_SKILL(pers,FS_SK_PWRMAX)*0.1 + (PERS_SKILL(pers,FS_SK_STR) + _COMP(MIN(PERS_LEVEL(pers),10),_X0)*(1 - _IComp)) * _Xs * 1.2;

	// damage absorb
	Ap = MIN(MAX((PERS_SKILL(opp,FS_SK_ENDUR) - (avgBS/5)) * (_Ap-_Ap0) / (avgIS/2), 0) + _Ap0, opp->art ? 0.8: 0.07);

	fE = randRoll(pE,&(opp->_rs[_RSC_EVD])) && !randRoll(pAE,&(pers->_rs[_RSC_AEVD]));
	fC = randRoll(pC,&(pers->_rs[_RSC_CRT])) && !randRoll(pAC,&(opp->_rs[_RSC_ACRT]));
	fB = randRoll(pB,&(opp->_rs[_RSC_BLK])) && !randRoll(pAB,&(pers->_rs[_RSC_ABLK]));
	if (opp->flags & (FS_PF_STUNNED | FS_PF_MAGIC)) fE = fB = false;
	if ((pers->flags & FS_PF_CRITSIMILAR) && (opp->flags & FS_PF_CRITSIMILAR)) fC = true;	// always crit the similar which always crits you :)

	// checking combo
	animData = NULL;
	fs_persAddComboItem(pers,part);
	cmb = fs_persCheckCombo(pers);
	if (cmb && (fE || fB)) {
		cmbP = 1 - (pers->level - cmb->level)*_CMBp;
		if (!cmb->level || randRoll(cmbP,NULL)) fE = fB = false;
		DEBUG("COMBO PRIORITY [level: %d, cmbP: %.2f]",cmb->level,cmbP);
	}

	if (fE) fC = false;
	if (fC) fB = false;
	if (fE && fB) {
		if (pE < pB) fE = false;
		else fB = false;
	}
	
	dmg = 0;
	dmgA = 0;
	if (!fE && !fB && cmb) {	// executing combo
		DEBUG("COMBO [id: %d, auxEffId: %d, level: %d]",cmb->id,cmb->auxEffId,cmb->level);
		v_reset(pers->effVec,0);
		while ((eff = v_each(pers->effVec,0))) {
			if ((eff->flags & FS_PEF_ACTIVE) || !(eff->flags & FS_PEF_AUX)) continue;
			if (eff->id == cmb->auxEffId) break;
		}
		if (eff) {
			eff->cnt = 1;
			eff->flags &= ~FS_PEF_PASSTURN;
			if (fs_persUseEffect(pers,eff,pers,NULL) == OK) animData = eff->animData;
		}
		cmb->useCnt++;
		pers->cmbSize = 0;
	}
	
	fs_persGetCharge(pers,FS_PDT_PHYSICAL,&charge, true);
	/*calc dmg*/
	if (!fE && !fB) {
		dmg = randDouble(0,dmgMax-dmgMin+1,NULL)/2 + randDouble(0,dmgMax-dmgMin+1,NULL)/2 + dmgMin;
		if (randRoll(charge.critProb,NULL)) fC = true;
		if (opp->flags & FS_PF_MAGIC) fC = false;
		dmgA = dmg;
		if (fC) {
			dmg *= _Cx;
			Ap = 0;
		}
		dmg *= 1-Ap; 
		dmg += dmg * charge.dmgX;	// dmgX charge
		if (pers->flags & FS_PF_DEFENDED) dmg *= _dmgDx;
		if (opp->flags & FS_PF_DEFENDED) dmg *= _dmgDx;
		dmg = MAX(dmg,1);
		
		dmgA *= 1-Ap; 
		dmgA += dmgA * charge.dmgX;	// dmgX charge
		if (pers->flags & FS_PF_DEFENDED) dmgA *= _dmgDx;
		if (opp->flags & FS_PF_DEFENDED) dmgA *= _dmgDx;
		dmgA = MAX(dmgA,1);
		
	}
	/* if (!fE && !fB) {
	dmg = randDouble(0,dmgMax-dmgMin+1,NULL)/2 + randDouble(0,dmgMax-dmgMin+1,NULL)/2 + dmgMin;
	if (randRoll(charge.critProb,NULL)) fC = true;
	if (opp->flags & FS_PF_MAGIC) fC = false;
	dmgA = dmg; //tudu fix shipi
	if (fC) {
		//Расчет урона и защиты от крит урона (Физический)
		cxMagDmgInt = PERS_SKILL(pers,FS_SK_FIZCRITDMX);
		cxMagDefInt = PERS_SKILL(opp,FS_SK_FIZCRITDEF);
		if(cxMagDmgInt > 0) cxMagDmg = cxMagDmgInt / 100.0;
		if(cxMagDefInt > 0) cxMagDef = cxMagDefInt / 100.0;
		cxMagAdd = cxMagDmg - cxMagDef;

		dmg *= (_Cx + cxMagAdd);
		Ap = 0;
	}
	dmg *= 1-Ap;
	dmg += dmg * charge.dmgX;	// dmgX charge
	if (pers->flags & FS_PF_DEFENDED) dmg *= _dmgDx;
	if (opp->flags & FS_PF_DEFENDED) dmg *= _dmgDx;
	dmg = MAX(dmg,1);

	//Снижение физического урона в блоке скилл
	dmgDxPlus = 0.0;
	dmgDxPlusInt = PERS_SKILL(opp, FS_SK_RSTPHYSICBLOCK);
	if(dmgDxPlusInt > 0) dmgDxPlus = dmgDxPlusInt / 100.0;
	if (opp->flags & FS_PF_DEFENDED) dmg *= _dmgDx - dmgDxPlus;
	dmg = MAX(dmg,1);
	} */
	
	saveDMG = dmg; //Сохраним для аур
	/*end calc dmg*/
	DEBUG("[%d (%d) ===>>> %d (%d)], part=%d, wpnEff=%d",pers->id,PERS_LEVEL(pers),opp->id,PERS_LEVEL(opp),part,wpnEff);
	DEBUG("avgBS=%.2f, avgIS=%.2f, evP1=%.2f, evP2=%.2f, pE=%.2f, pC=%.2f, pB=%.2f, pAE=%.2f, pAC=%.2f, pAB=%.2f, Ap=%.2f", avgBS, avgIS, evP1, evP2, pE, pC, pB, pAE, pAC, pAB, Ap);
	DEBUG("dmgMin=%.2f, dmgMax=%.2f, dmg=%.2f", dmgMin, dmgMax, dmg);
	DEBUG("fE=%d, fC=%d, fB=%d", fE, fC, fB);

	kick = fE ? 1: fC ? 2: !fB ? 3: 4;	// 1 - ������, 2 - ����������� ����, 3 - ����, 4 - ���� ������������
	rnd = randInt(0,0xFFFF,NULL);
	
	//VSE SYDA
	int stoikost = PERS_SKILL(opp, FS_SK_STOIKOST);
	
	if(opp->flags & FS_PF_BOT && opp->botTypeId > 0 && PERS_BOTDMGSKILL(pers, opp->botTypeId) > 0) {
		dmg += MAX(0, (dmg * PERS_BOTDMGSKILL(pers, opp->botTypeId)) / 100);
	}

	if(stoikost != 0){
		float  percent = (stoikost/100.0) / 10; // stoikost delim na sto
		dmg = dmg - (dmg * percent);
	}
	
	
	fs_persSetEvent(pers,FS_PE_ATTACK,"iiiiis",pers->id,opp->id,kick,part,rnd,animData);
	fs_persSetEvent(opp,FS_PE_ATTACK,"iiiiis",pers->id,opp->id,kick,part,rnd,animData);
	
	ndmg = dmg;
	dmg = fs_persDamage(opp,dmg,FS_PDT_PHYSICAL,false,pers);	// damage
	
	
	//DEBUGXXX("DAMAGE (persId: %d, status: %.2f)",pers->id,dmg);
	
	if(charge.vamp > 0) { //Восставовление от вампирика
		_chargeVampInt = PERS_SKILL(pers,FS_SK_CHVAMPPLUS);
		if(_chargeVampInt > 0) _chargeVamp = _chargeVampInt / 100.0;
	}else if(charge.vamp < 0){ //Урон от вампирика
		_chargeVampInt = PERS_SKILL(pers,FS_SK_CHVAMPMINUS);
		if(_chargeVampInt > 0) _chargeVamp = _chargeVampInt / 100.0;
		//if(_chargeVamp > charge.vamp) charge.vamp = 0; //Почему я это заккоментил? Потому что Саня ты дебил!
	}

	if(pers->id == 1){
		//DEBUGXXX("VAMPA (skill: %d, charge.vamp: %.2f, _chargeVampInt: %d, _chargeVamp: %.2f)",PERS_SKILL(pers,FS_SK_CHVAMPPLUS),charge.vamp,_chargeVampInt,_chargeVamp);
	}
	
	fs_persDamage(pers,-dmg*(charge.vamp + _chargeVamp),kick,false,NULL);	// vampiric standart
		
	fs_fightSaveLog(pers,FS_FLC_KICK,kick,part,dmg,NULL,false);
	
	//CHECK_USR_AURA_EFF

	v_reset(pers->effVec,0);
    while ((auraEff = v_each(pers->effVec,0))) {
        if (!(auraEff->flags & FS_PEF_ACTIVE) || !(auraEff->flags & FS_PEF_AURA)) continue;
        if (auraEff->flags & FS_PEF_AURA) break;
    }
	if(auraEff && (auraEff->flags & FS_PEF_AURA) && (auraEff->flags & FS_PEF_ACTIVE)){ // && !fE && !fB //Anti Block and anti evade
		do{
			if(charge.razpeleny > 0){
				auraEff->flags |= FS_PEF_DROP;
				break;
			}
			if ((auraEff->e_yarost > 0) && (PERS_MP(pers) > auraEff->e_yarost)) {
				fs_persConsumeManna(pers,auraEff->e_yarost,false);
				
				dmgAura = fs_persRecalcDmgAura(pers, opp, auraEff);
				
				if (pers->flags & FS_PF_DEFENDED) dmgAura *= _dmgDx;
				if (opp->flags & FS_PF_DEFENDED) {
					dmgAura *= _dmgDx;
				}
				
				dmgAura += dmgAura * charge.aura_dmgX;	// dmgX
	
				DEBUG("dmg=%.2f, dmgAura=%.2f, aoeCnt=%d", ndmg, dmgAura, auraEff->aoeCnt);
				
				if((auraEff->aoeCnt - 1) > 0){
					//Нужно бить сразу нескольких..
					i=0;
					v_reset(pers->fight->persVec,&vi);
	
					//определем раздаточный урон
					dmgAuraAoe = dmgAura;
					//dmgAuraAoe = (dmgAura / auraEff->aoeCnt); //убрал)
	
					while ((p = v_each(pers->fight->persVec,&vi))) {
						if ((p->teamNum == pers->teamNum) || p->id == pers->opponent->id) continue;
						if ((p->status != FS_PS_FIGHTING) && (p->status != FS_PS_ACTIVE) && (p->status != FS_PS_PASSIVE)) continue;
						if(i == (auraEff->aoeCnt - 1)) break;
						
						
						dmgAuraAoe = fs_persDamage(p,dmgAuraAoe,auraEff->dmgType,randRoll(charge.aura_critProb,NULL),pers);	// пелена
						
						fs_persSetEvent(pers,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,0,(int)dmgAuraAoe,0,"");
						fs_persSetEvent(p,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,0,(int)dmgAuraAoe,0,"");
						
						i++;
					}
				}
				fs_persDamage(pers,-dmgAura*charge.aura_vamp,kick,false,NULL);	// vampiric aura
	
				dmgAura = fs_persDamage(opp,dmgAura,auraEff->dmgType,randRoll(charge.aura_critProb,NULL),pers);	// пелена
				fs_fightSaveLog(pers,FS_FLC_EFFECTUSE,kick,dmgAura,0,NULL,false);
			}
			if((PERS_MP(pers) < auraEff->e_yarost)){
				auraEff->flags |= FS_PEF_DROP;
			}
		}while(0);
	}
	
	if(opp->stunCnt > 0) opp->stunCnt--;
	if(opp->stunSafeCnt > 0) opp->stunSafeCnt--;
	fs_persRecalcEffects(pers);
	fs_persRecalcEffects(opp);
	
	int shipi = PERS_SKILL(opp,FS_SK_SHIP); // shipiept xD u protivnika
	if(shipi > 100) shipi = 100; // chtobi ne ebatsa s etim potom
	if(shipi){
		// esli opponent pod shipami
		float  percent = shipi/100.0; // shipi delim na sto
		float ship_dmg = dmgA*percent;
		fs_persDamage(pers,ship_dmg,FS_FLC_SHIP,false,opp);
		if(ship_dmg > 0){
			fs_fightSaveLog(opp,FS_FLC_KICK,FS_FLC_SHIP,part,dmgA*percent,NULL,false);
			fs_persRecalcEffects(pers);
			fs_persRecalcEffects(opp);
		}
	}
	
	int penetration = PERS_SKILL(pers,FS_SK_PENETRATION); // penetration xD u protivnika
	if(penetration > 100) penetration = 100; // chtobi ne ebatsa s etim potom
	if(penetration){
		// esli opponent pod penetration
		float  percent = penetration/100.0; // penetration delim na sto
		float  penetration_dmg = dmgA*percent; //dmg
		fs_persDamage(opp,penetration_dmg,FS_FLC_PROB,false,pers);
		if(penetration_dmg > 0){
			fs_fightSaveLog(pers,FS_FLC_KICK,FS_FLC_PROB,part,penetration_dmg,NULL,false);
		}
		fs_persRecalcEffects(opp);
	}
	
	int vampir = PERS_SKILL(pers,FS_SK_VAMPIR); // vampir xD u protivnika
	if(vampir > 100) vampir = 100; // chtobi ne ebatsa s etim potom
	if(vampir){
		// esli opponent pod vampir
		float  percent = vampir/100.0; // vampir delim na sto
		float  vampir_dmg = -dmgA*percent; //dmg
		fs_persDamage(pers,vampir_dmg,FS_FLC_VAMP,false,pers);
		if(vampir_dmg < 0){
			fs_fightSaveLog(pers,FS_FLC_KICK,FS_FLC_VAMP,part,vampir_dmg,NULL,false);
		}
		fs_persRecalcEffects(pers);
	}
	
	if (!fE && randRoll(charge.stunProb,NULL) && !(pers->flags & FS_PF_STUNNED) && !(opp->flags & FS_PF_STUNNED) && opp->stunSafeCnt <= 0) {	// stunning the opponent if no evade //Станим только в случае обхода защиты opp->stunSafeCnt.
		fs_persStun(pers,opp,charge.stunTime,charge.stunCnt);
	}
	fs_persDischarge(pers,FS_PDT_PHYSICAL);

	pers->timeoutCnt = 0;

	int y_add = 0;
	if(fs_persGetTurn(opp) == OK) {
		fs_persAddYarost(pers, 3);
	}
	
	pers->userUdarCnt++;
	
	/*int time_current = time(NULL); //Начисление ярости по времени
	int yarost_time = pers->yarost_time;
	if(!yarost_time || yarost_time == 0 || yarost_time == NULL){
		pers->yarost_time = time_current;
	}
	if(time_current > yarost_time){
		int yarost_add = (int)((time_current - yarost_time) / 3); //3 секунды там короче
		if(yarost_add > 0 && pers->yarost < 100){
			pers->yarost += yarost_add;
			if(pers->yarost > 100){
				pers->yarost = 100;
			}
			pers->yarost_time = time_current;
		}
	}*/
	return OK;
}

errno_t fs_persAddYarost(fs_pers_t *pers, int cnt) {
	int y_add;
	if (!pers || (cnt <= 0)) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (pers->status == FS_PS_DEAD) return ERR_WRONG_STATE;
	
	if(pers->yarost < pers->yarost_max){
		if(pers->yarost >= pers->yarost_max){
			y_add = pers->yarost_max - (pers->yarost - cnt);
			pers->yarost = pers->yarost_max;
		}else{
			y_add = cnt;
		}
		pers->yarost += y_add;
		if(y_add > 0){
			fs_persSetEvent(pers, FS_PE_ENERGYREGEN,"ii",pers->id,y_add); //standart, maybe future not static param :)
		}
	}
	return OK;
}

errno_t fs_persStun(fs_pers_t *pers, fs_pers_t *opp, int stunTime, int stunCnt) {
	fs_persEff_t *eff;

	if (!pers || (stunTime <= 0)) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (pers->status == FS_PS_DEAD) return ERR_WRONG_STATE;
	if(stunCnt <= 0) stunCnt = 3;
	if(stunCnt >= 7) stunCnt = 7; //Нельзя застанить больше 7 ходов подряд... хоть как блять!

	//Для этого передаем состояние кол-ва в pers
	if(pers->opponent) pers->opponent->stunCnt = stunCnt;
	else pers->stunCnt = stunCnt;

	if(PERS_INTSKILL(opp,FS_SK_ANTISTUN) > 0 || PERS_EXTSKILL(opp,FS_SK_ANTISTUN) > 0) return ERR_WRONG_STATE;
	eff = fs_persEffCreate(0);
	eff->code = FS_PEC_STUN;
	eff->cnt = 1;
	eff->actTime = stunTime;
	eff->title = strdup("stun");
	fs_persUseEffect(pers,eff,opp,NULL);
	fs_persEffDelete(eff);
	if(pers->opponent) fs_persGetTurn(pers->opponent);
	return OK;
}	

errno_t fs_persGetTurn(fs_pers_t *pers) {
	fs_pers_t    *opp;
	
	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	
	opp = pers->opponent;
	
	if ((pers->status == FS_PS_ACTIVE) || (pers->status == FS_PS_DEAD) || !opp || (opp->status == FS_PS_DEAD)) return ERR_WRONG_STATE;
	if (pers->flags & FS_PF_STUNNED) opp->flags &= ~FS_PF_GETTURN;	// keep the chance to attack while stunned
	if ((pers->flags & FS_PF_GETTURN) && (opp->flags & FS_PF_GETTURN)) {
		pers->status = FS_PS_FIGHTING;
		pers->flags &= ~FS_PF_GETTURN;
		opp->status = FS_PS_FIGHTING;
		opp->flags &= ~FS_PF_GETTURN;
		return OK;
	}
	pers->flags |= FS_PF_GETTURN;
	pers->status = FS_PS_ACTIVE;
	opp->status = FS_PS_PASSIVE;
	
	/*fs_fightPersStatus(pers);
	if(opp){
		fs_fightPersStatus(opp);
	}*/
	if (pers->flags & FS_PF_STUNNED) return fs_persGetTurn(opp);
	pers->mtime = fs_stime;
	fs_persSetEvent(pers,FS_PE_ATTACKNOW,"i",PF_CLMASK(opp->flags));
	fs_persSetEvent(opp,FS_PE_ATTACKWAIT,0);
	return OK;
}

errno_t fs_persDie(fs_pers_t *pers, fs_pers_t *killer) {
	fs_pers_t    *opp;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (pers->status == FS_PS_DEAD) return ERR_WRONG_STATE;
	if (pers->killer) killer = pers->killer;	// earlier killer is prefered
	DEBUG("PERSONAGE DIED (persId: %d)",pers->id);
	fs_fightSaveLog(pers,FS_FLC_DEATH,((pers->flags & FS_PF_TIMEOUTKILL) > 0),(killer ? killer->id : 0),0,NULL,false);
	fs_persSetEvent(pers,FS_PE_DEATH,"i",pers->id);
	if (pers->opponent) {
		opp = pers->opponent;
		opp->status = FS_PS_FIGHTING;
		opp->flags &= ~FS_PF_GETTURN;
		opp->opponent = NULL;
		fs_persSetEvent(opp,FS_PE_DEATH,"i",pers->id);
	}
	if (killer) {
		killer->killCnt++;
		if ((pers->kind != killer->kind) && !(pers->flags & FS_PF_BOT) && ((killer->level - pers->level) < 3)) killer->enemyKillCnt++;
	}	
	pers->status = FS_PS_DEAD;
	pers->flags &= ~(FS_PF_GETTURN | FS_PF_TIMEOUTKILL | FS_PF_LIFELESS);
	pers->opponent = NULL;
	pers->killer = NULL;
	PERS_INTSKILL(pers,FS_SK_HP) = PERS_EXTSKILL(pers,FS_SK_HP) = 0;
	//if die
	//fs_fightLockMutex(pers->fight); //lock_have!!!
	fs_fightDeadCnt(pers->fight, pers->teamNum, 1);
	//fs_fightUnlockMutex(pers->fight); //unlock_have!!!
	
	return OK;
}

errno_t fs_persPersIntelligence(fs_pers_t *pers) {
	fs_fight_t   *fight;
	lua_State    *L;
	int          stime = fs_stime;
	
	//pettime сделать)))
	
	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	
	if(!pers->ctrlFunc) return OK;
	
	//if(!pers->petLevel) return OK;
	
	//if (pers->status == FS_PS_CREATED) pers->status = FS_PS_PENDING;	// going online
	//if (pers->botActionTime > stime) return OK;
	//pers->botActionTime = stime + randInt(2,3,NULL);

	/*if (pers->status != FS_PS_ACTIVE) return OK;
	if (pers->mtime > (stime - 1)) return OK;	// don't hit if less than a second passed
	fs_persAttack(pers,randInt(1,3,NULL),false);
	return OK;*/

	fight = pers->fight;
	L = fight->L;
	if (!pers->ctrlFunc) {
		WARN("No control function given (fightId: %d, persId: %d)",fight->id,pers->id);
		return ERR_INIT;
	}
	
	int time_current = fs_stime;
	int pet_time = 0;
	if(!pers->petRest || pers->petRest == 0){
		pet_time = randInt(PET_PERIOD_TIME_MIN, PET_PERIOD_TIME_MAX, NULL);
		if(pers->petReady){ pet_time = randInt(PET_TIME_MIN, PET_TIME_MAX, NULL); }
		pers->petRest = pet_time;
		//DEBUGXXX("PET REST #1 (time_current: %d, pet_time: %d)",time_current,pet_time);
		//DEBUGXXX("PET REST #2 (pers petRest: %d, pers petReady: %d)",pers->petRest,pers->petReady);
	}
	pet_time = pers->petRest;
	if(time_current > (pers->pettime + pet_time)){
		if(pers->userUdarCnt < PET_USER_MIN_STUCK_CNT){
			return OK;
		}
		//DEBUGXXX("PET REST #3 (time_current: %d, pers pettime: %d)",time_current,pers->pettime);
		pers->pettime = fs_stime;
		
		pet_time = randInt(PET_PERIOD_TIME_MIN, PET_PERIOD_TIME_MAX, NULL);
		if(pers->petReady){ pet_time = randInt(PET_TIME_MIN, PET_TIME_MAX, NULL); }
		pers->petRest = pet_time;
		//DEBUGXXX("PET REST #4 (time_current: %d, pers pettime: %d)",time_current,pers->pettime);
	}else{
		//DEBUGXXX("PET REST #5 (time_current: %d, pers pettime: %d)",time_current,pers->pettime);
		return OK;
	}
	
	pers->userUdarCnt = 0;
	//DEBUGXXX("BUFF SUKA!!!");
	
	pthread_mutex_lock(&(fight->mutex_lua));
	if (lua_gettop(L) > 0) { 
		//DEBUGXXX("Lua stack not empty");	// [DEBUG]
	}
	lua_settop(L,0);

	//PARAMS LUA INIT
	fs_fightLuaInitParams(fight);
	
	// updating registry
	luaif_pushptr(L,fight,"fs_fight_t");
	lua_setfield(L,LUA_REGISTRYINDEX,"fight");
	luaif_pushptr(L,pers,"fs_pers_t");
	lua_setfield(L,LUA_REGISTRYINDEX,"pers");

	// "my" global table
	lua_newtable(L);
	LUAIF_TABLE_ADDFIELD(L,"stime",stime,integer);
	LUAIF_TABLE_ADDFIELD(L,"petLevel",pers->petLevel,integer);
	LUAIF_TABLE_ADDFIELD(L,"petReady",pers->petReady,integer);
	luaif_pushptr(L,fight,"fs_fight_t");
	lua_setfield(L,-2,"fightPtr");
	luaif_pushptr(L,pers,"fs_pers_t");
	lua_setfield(L,-2,"persPtr");
	LUAIF_TABLE_ADDFIELD(L,"persId",pers->id,integer);
	if (PERS_OPP_ID(pers)) luaif_pushptr(L,pers->opponent,"fs_pers_t"); else lua_pushnil(L);
	lua_setfield(L,-2,"oppPtr");
	LUAIF_TABLE_ADDFIELD(L,"status",pers->status,integer);
	LUAIF_TABLE_ADDFIELD(L,"teamNum",pers->teamNum,integer);
	lua_setglobal(L,"my");
	
	// running control function
	lua_getglobal(L,pers->ctrlFunc);
	if (!lua_isfunction(L,-1)) {
		//DEBUGXXX("Can't find function '%s:%s' (fightId: %d, persId: %d)",pers->ctrlFile,pers->ctrlFunc,fight->id,pers->id);
		lua_pop(L,1);
	}
	if (luaif_docall(L,0) != 0) {
		//DEBUGXXX("Error in function '%s:%s' (fightId: %d, persId: %d)",pers->ctrlFile,pers->ctrlFunc,fight->id,pers->id);
	}
	pthread_mutex_unlock(&(fight->mutex_lua));
	return OK;
}

errno_t fs_persBotIntelligence(fs_pers_t *pers) {
	fs_fight_t   *fight;
	lua_State    *L;
	int          stime = fs_stime;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}

	if(pers->opponent && pers->opponent->flags & FS_PF_STUNNED) {
		if (pers->mtime > (stime - 2)) return OK;	// don't hit if less than a second passed
	}else{
		if (pers->mtime > (time(NULL) - 1)) return OK;	// don't hit if less than a second passed
	}

	if (pers->status == FS_PS_CREATED) {
		pers->status = FS_PS_FIGHTING;
	}	// going online
	if (pers->botActionTime > stime) return OK;
	if (pers->status != FS_PS_ACTIVE) return OK;
	
	if(pers->status == FS_PS_ACTIVE && !pers->ctrlFunc){
		//DEBUGXXX("BOTATTACK");
		fs_persAttack(pers,randInt(1,3,NULL),false);
		return OK;
	}
	pers->botActionTime = stime + randInt(2,3,NULL);
	
	
	fight = pers->fight;
	L = fight->L;
	
	if (!pers->ctrlFunc) {
		WARN("No control function given (fightId: %d, persId: %d)",fight->id,pers->id);
		return ERR_INIT;
	}
	
	pthread_mutex_lock(&(fight->mutex_lua));
	if (lua_gettop(L) > 0) WARN("Lua stack not empty");	// [DEBUG]
	lua_settop(L,0);
	
	//PARAMS LUA INIT
	DEBUG("PARAMS_LUA_INIT");
	fs_fightLuaInitParams(fight);

	// updating registry
	luaif_pushptr(L,fight,"fs_fight_t");
	lua_setfield(L,LUA_REGISTRYINDEX,"fight");
	luaif_pushptr(L,pers,"fs_pers_t");
	lua_setfield(L,LUA_REGISTRYINDEX,"pers");

	// "my" global table
	lua_newtable(L);
	LUAIF_TABLE_ADDFIELD(L,"stime",stime,integer);
	luaif_pushptr(L,fight,"fs_fight_t");
	lua_setfield(L,-2,"fightPtr");
	luaif_pushptr(L,pers,"fs_pers_t");
	lua_setfield(L,-2,"persPtr");
	LUAIF_TABLE_ADDFIELD(L,"persId",pers->id,integer);
	if (PERS_OPP_ID(pers)) luaif_pushptr(L,pers->opponent,"fs_pers_t"); else lua_pushnil(L);
	lua_setfield(L,-2,"oppPtr");
	LUAIF_TABLE_ADDFIELD(L,"status",pers->status,integer);
	LUAIF_TABLE_ADDFIELD(L,"teamNum",pers->teamNum,integer);
	lua_setglobal(L,"my");
	
	// running control function
	lua_getglobal(L,pers->ctrlFunc);
	if (!lua_isfunction(L,-1)) {
		WARN("Can't find function '%s:%s' (fightId: %d, persId: %d)",pers->ctrlFile,pers->ctrlFunc,fight->id,pers->id);
		lua_pop(L,1);
	}
	if (luaif_docall(L,0) != 0) {
		WARN("Error in function '%s:%s' (fightId: %d, persId: %d)",pers->ctrlFile,pers->ctrlFunc,fight->id,pers->id);
	}
	pthread_mutex_unlock(&(fight->mutex_lua));
	return OK;
}

// �������� �������� ��� ��������� ��������� ������ ����� � ������ ������
double fs_persGetCost(fs_pers_t *pers) {
	double  cost;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	cost = 
		PERS_SKILL(pers,FS_SK_STR) +
		PERS_SKILL(pers,FS_SK_INT) +
		PERS_SKILL(pers,FS_SK_DEX) +
		PERS_SKILL(pers,FS_SK_ENDUR) +
		PERS_SKILL(pers,FS_SK_VIT) +
		(PERS_SKILL(pers,FS_SK_PWRMIN)*0.1 + PERS_SKILL(pers,FS_SK_PWRMAX)*0.1)/(2*_Xs) +
		PERS_SKILL(pers,FS_SK_XHPMAX)/_Vs +
		(PERS_SKILL(pers,FS_SK_MAGPWRMIN)*0.1 + PERS_SKILL(pers,FS_SK_MAGPWRMAX)*0.1)/(2*_Xs) +
		PERS_SKILL(pers,FS_SK_WISDOM)
	;
	return MAX(cost,0);
}

// �������� �������� ��� ������� ������������ �������
double fs_persGetCost2(fs_pers_t *pers) {
	double  cost;
	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (PERS_LEVEL(pers) >= 11) { // ��� �����
		cost = 
			PERS_SKILL(pers,FS_SK_INT) +
			PERS_SKILL(pers,FS_SK_DEX) +
			PERS_SKILL(pers,FS_SK_ENDUR)
		;
		cost = cost * 2;
	} else {
		cost = 
			PERS_SKILL(pers,FS_SK_STR) +
			PERS_SKILL(pers,FS_SK_INT) +
			PERS_SKILL(pers,FS_SK_DEX) +
			PERS_SKILL(pers,FS_SK_ENDUR) +
			PERS_SKILL(pers,FS_SK_VIT) +
			(PERS_SKILL(pers,FS_SK_PWRMIN)*0.1 + PERS_SKILL(pers,FS_SK_PWRMAX)*0.1)/(2*_Xs) +
			PERS_SKILL(pers,FS_SK_XHPMAX)/_Vs 
			- (_COMP(PERS_LEVEL(pers),_X0) + _COMP(PERS_LEVEL(pers),_V0))*_IComp
		;
	}
	return MAX(cost,0);
}

double fs_persGetExp(fs_pers_t *pers, fs_pers_t *opp, double dmg) {
	double       c1, c2, cK, exp, expMax;

	if (!pers || !opp) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	bool pers_bot = ((pers->flags & FS_PF_SHADOW));
	if ((pers->flags & FS_PF_BOT) && !pers_bot) return 0;
	c1 = fs_persGetCost(pers);
	c2 = fs_persGetCost(opp);
	cK = c1 ? c2 / c1: 1000;
	cK = MIN(cK, 3);
	expMax = BASE_EXP(PERS_LEVEL(opp)) * cK;
	if (opp->expX > 0) expMax *= opp->expX;
	exp = PERS_HPMAX(opp) ? (dmg * expMax) / PERS_HPMAX(opp): 0;
	DEBUG("dmg: %.2f, c1: %.2f, c2: %.2f, cK: %.2f, expX: %.2f, expMax: %.2f, exp: %.2f", dmg, c1, c2, cK, opp->expX, expMax, exp);
	return exp;
}

double fs_persGetHonor(fs_pers_t *pers, fs_pers_t *opp, double dmg) {
	double       c1, c2, cK, honor, honorMax;

	if (!pers || !opp) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (pers->flags & FS_PF_BOT) return 0;
	if ((opp->flags & FS_PF_BOT) || (opp->kind == pers->kind) || ((pers->level - opp->level) > 3)) return 0;
	c1 = fs_persGetCost(pers);
	c2 = fs_persGetCost(opp);
	cK = c1 ? c2 / c1: 1000;
	cK = MIN(cK, 1.2);
	honorMax = BASE_HONOR(PERS_LEVEL(opp)) * cK;
	honor = PERS_HPMAX(opp) ? (dmg * honorMax) / PERS_HPMAX(opp): 0;
	return fs___persMaxHonor(pers,opp,honor);
}

double fs___persMaxHonor(fs_pers_t *pers, fs_pers_t *opp, double honor) {
	fs_persHonorData_t     *honorData;
	double                 honorMax;

	honorMax = BASE_HONOR(PERS_LEVEL(opp)) * 2;
	honor = MIN(honor, honorMax);
	v_reset(pers->honorDataVec,0);
	while ((honorData = v_each(pers->honorDataVec,0))) {
		if (honorData->pers == opp) break;
	}
	if (honorData) {
		honor = MIN(honor, honorMax - honorData->honor);
		honorData->honor += honor;
	} else {
		honorData = malloc(sizeof(fs_persHonorData_t));
		memset(honorData,0,sizeof(fs_persHonorData_t));
		honorData->pers = opp;
		honorData->honor = honor;
		v_push(pers->honorDataVec,honorData);
	}
	return honor;
}

double fs_persDamage(fs_pers_t *pers, double dmg, int dmgType, bool crit, fs_pers_t *activator) {
	fs_persEff_t      *eff;
	viter_t           vi;
	int               i, absorb = 0, cxMagDefInt = 0, cxMagDmgInt = 0;
	double            pResist = 0, cxMagDef = 0, cxMagDmg = 0, cxMagAdd = 0;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if ((pers->status == FS_PS_DEAD) || (pers->flags & FS_PF_LIFELESS)) return 0;
	if ((pers->flags & FS_PF_IMMORTAL) && (dmg > 0)) dmg = 0;
	if (dmg > 0) {	// damage
		if (!dmgType) dmgType = FS_PDT_PHYSICAL;
		if (activator) {
			if (crit || ((dmgType & ~OLD_DMGTYPE) && randRoll((PERS_SKILL(activator,FS_SK_MAGCRIT) + 0.5*PERS_SKILL(activator,FS_SK_WILL)/MAX(MAX(PERS_LEVEL(activator)-10,PERS_LEVEL(pers)-10),1))/100.0,NULL))) {	// magic crit
				//Расчет урона и защиты от крит урона
				cxMagDmgInt = PERS_SKILL(activator,FS_SK_MAGCRITDMX);
				cxMagDefInt = PERS_SKILL(pers,FS_SK_MAGCRITDEF);
				if(cxMagDmgInt > 0) cxMagDmg = cxMagDmgInt / 100.0;
				if(cxMagDefInt > 0) cxMagDef = cxMagDefInt / 100.0;
				cxMagAdd = cxMagDmg - cxMagDef;

				dmg *= (_Cx + cxMagAdd);
				crit = true;
			}
			pResist = fs_persPartResist(fs_persResistProb(activator,pers,dmgType));	// partial resist
			if (pResist > 0) dmg *= (1 - pResist);
		}
		v_reset(pers->effVec,&vi);
		while ((eff = v_each(pers->effVec,&vi))) {
			if (!(eff->flags & FS_PEF_ACTIVE) || !(eff->dmgType & dmgType)) continue;
			if (eff->code == FS_PEC_ABSORB) {
				i = MIN(dmg,eff->i1);
				dmg -= i;
				eff->i1 -= i;
				absorb += i;
				if (eff->i1 <= 0) eff->flags |= FS_PEF_DROP;
			}
		}
		dmg = MIN(PERS_HP(pers),dmg);
	} else if (dmg < 0) {	// heal
		dmg = -MIN(-dmg,PERS_HPMAX(pers)-PERS_HP(pers));
	}
	PERS_INTSKILL(pers,FS_SK_HP) -= (int)dmg;
	PERS_EXTSKILL(pers,FS_SK_HP) -= (int)dmg;
	if (PERS_HP(pers) <= 0) {
		pers->flags |= FS_PF_LIFELESS;
		pers->killer = activator;
	}	
	if (dmg && activator && (activator != pers)) {	// updating statistics
		if (dmg > 0) {
			activator->dmg += dmg;
			activator->exp += fs_persGetExp(activator,pers,dmg);
			activator->honor += fs_persGetHonor(activator,pers,dmg);
		} else {
			activator->heal += -dmg;
			activator->exp += fs_persGetExp(activator,activator,-dmg);
		}
	}
	fs_persSetEvent(pers,FS_PE_DAMAGE,"iiiiiii",pers->id,(int)dmg,dmgType,crit,absorb,(activator ? activator->id : 0),(int)(pResist * 100));
	if (PERS_OPP_ID(pers)) fs_persSetEvent(pers->opponent,FS_PE_DAMAGE,"iiiiiii",pers->id,(int)dmg,dmgType,crit,absorb,(activator ? activator->id : 0),(int)(pResist * 100));
	return dmg;
}

int fs_persConsumeManna(fs_pers_t *pers, int manna, bool silent) {
	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if ((pers->status == FS_PS_DEAD) || (pers->flags & FS_PF_LIFELESS)) return 0;
	if ((pers->flags & FS_PF_IMMORTAL) && (manna > 0)) manna = 0;
	if (manna > 0) {	// consumption
		manna = MIN(PERS_MP(pers),manna);
	} else if (manna < 0) {	// addition
		manna = -MIN(-manna,PERS_MPMAX(pers)-PERS_MP(pers));
	} else return 0;
	PERS_INTSKILL(pers,FS_SK_MP) -= manna;
	PERS_EXTSKILL(pers,FS_SK_MP) -= manna;
	if (!silent) {
		fs_persSetEvent(pers,FS_PE_MANNACONSUM,"ii",pers->id,manna);
		if (PERS_OPP_ID(pers)) fs_persSetEvent(pers->opponent,FS_PE_MANNACONSUM,"ii",pers->id,manna);
	}	
	return manna;
}

// usageStatus:
// when return status is not OK:
//    0 - no details
//  -10 - can't apply to anyone from my team
//  -20 - can't apply to anyone from the opponent's team
//  -30 - denied by group ID
//  -40 - not enough manna
// when return status is OK:
//    0 - ok
//   10 - immunity
//   20 - magic hit chance roll failed
//   30 - full resist
errno_t fs_persUseEffect(fs_pers_t *pers, fs_persEff_t *eff, fs_pers_t *target, int *usageStatus) {
	errno_t           status;
	fs_persEff_t      *effCopy;
	fs_persEff_t      *effS;
	fs_persEff_t      *auraEff;
	int               i, stime, idx, _usageStatus, *effUsageStatuses, dmgDxPlusInt;
	double            dmg, aoeWeight, aoeRand, dmgDxPlus;
	bool              auxCall = false;
	vector_t          v1, v2;
	viter_t           vi;
	fs_pers_t         *p;
	fs_pers_t         *pAnim;
	fs_persCharge_t   charge;
	int _chargeVampInt = 0;
	float _chargeVamp = 0;

	if (!pers || !eff || !target) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (eff->flags & FS_PEF_ACTIVE) {
		WARN("Attempt to use an effect marked as FS_PEF_ACTIVE");
		return ERR_WRONG_STATE;
	}
	
	stime = fs_stime;
	if (!usageStatus) {
		auxCall = true;
		usageStatus = &_usageStatus;
	}
	*usageStatus = 0;
	if (eff->cnt <= 0) {
		if (!(eff->flags & FS_PEF_SPELL)) return ERR_GENERAL;	//	doesn't seem to be a spell effect
		if (PERS_OPP_ID(pers) && !(pers->flags & FS_PF_MAGIC)) return ERR_GENERAL;	// have an opponent, but not in the magic state
	}

	// ==================== CHECKING ====================
	
	// checking cooldown
	if (eff->cdrtime > stime) return ERR_GENERAL;

	// checking whether we can use the effect right now
	if ((pers->status != FS_PS_ACTIVE) && (eff->flags & FS_PEF_NEEDTURN)) return ERR_GENERAL;	// pers is not active
	if ((pers->status == FS_PS_DEAD) && !(eff->flags & FS_PEF_USEDEAD)) return ERR_GENERAL;	// pers is dead
	if (pers->flags & FS_PF_STUNNED) return ERR_GENERAL;	// pers is stunned

	// checking target
	if (eff->flags & (FS_PEF_TARGETSELF | FS_PEF_TARGETOPP)) {
		if (!(eff->flags & FS_PEF_TARGETSELF)) {
			if (!PERS_OPP_ID(pers)) return ERR_GENERAL;
			target = pers->opponent;
		} else if (!(eff->flags & FS_PEF_TARGETOPP)) {
			target = pers;
		} else {
			if (!PERS_OPP_ID(pers)) target = pers;
			else if ((target != pers) && (target != pers->opponent)) return ERR_GENERAL;
		}
	}

	// checking team
	if (eff->flags & (FS_PEF_TEAMSELF | FS_PEF_TEAMOPP)) {
		if (!(eff->flags & FS_PEF_TEAMSELF) && (pers->teamNum == target->teamNum)) {
			*usageStatus = -10;	// -10 - can't apply to anyone from my team
			return ERR_GENERAL;
		}
		else if (!(eff->flags & FS_PEF_TEAMOPP) && (pers->teamNum != target->teamNum)) {
			*usageStatus = -20;	// -20 - can't apply to anyone from the opponent's team
			return ERR_GENERAL;
		}
	}

	
	if ((eff->flags & FS_PEF_TARGETNOTUSER) && !(target->flags & FS_PF_BOT)){ //Нельзя использовать на игроков
		*usageStatus = -20;	// -20 - can't apply to anyone from the opponent's team
		return ERR_GENERAL;
	}
	
	// checking manna amount
	if ((eff->mp > 0) && (PERS_MP(pers) < eff->mp)) {
		*usageStatus = -40;	// -40 - not enough manna
		return ERR_GENERAL;
	}

	//check mana on aura
	if (eff->flags & FS_PEF_AURA && (eff->mp > 0) && (PERS_MP(pers) < eff->mp)) {
		*usageStatus = -40;	// -40 - not enough manna
		return ERR_GENERAL;
	}
	
	// checking arrowsCnt
	if (eff->flags & FS_PEF_BOW && pers->arrowsCnt <= 0) {
		*usageStatus = -46;	// -46 - not enough arrows
		return ERR_GENERAL;
	}
	
	// checking yarost amount
	if ((eff->e_yarost > 0 && !(eff->flags & FS_PEF_AURA)) && pers->yarost < eff->e_yarost) {
		*usageStatus = -45;	// -45 - not enough yarost
		return ERR_GENERAL;
	}
	
	status = fs___persEffectTargetCheck(pers,eff,target,usageStatus);
	if (status != OK) return status;

	// ==================== APPLYING ====================

	// setting up cooldown
	if (eff->cdTime > 0) {
		if (eff->cdGrpId) {
			v_reset(pers->effVec,0);
			while ((effCopy = v_each(pers->effVec,0))) {
				if (effCopy->flags & FS_PEF_ACTIVE) continue;
				if (effCopy->cdGrpId != eff->cdGrpId) continue;
				effCopy->cdrtime = stime + eff->cdTime;
			}
		} else eff->cdrtime = stime + eff->cdTime;
	}

	// taking manna
	if (eff->mp > 0) fs_persConsumeManna(pers,eff->mp,false);
	
	//taking yarost
	if (eff->e_yarost && !(eff->flags & FS_PEF_AURA)) pers->yarost -= eff->e_yarost; //FIX aura yarost--

	if(eff->flags & FS_PEF_BOW) pers->arrowsCnt -= 1;
	
	if(eff->code == FS_PEC_RESETCOMBO && target) {
		target->cmbSize = 0; //ISPRAVLENIE
		fs_persSetEvent(target, FS_PE_RESETCOMBO, 0);
		//fs_fightSaveLog(pers,FS_FLC_COMMON,1,pers->id,target->id,NULL,false);
	}
	//if(eff->flags & FS_PEF_CLCOMBO) { v_freeData(target->cmbVec); }
	
	DEBUG("EFFECT [%d --> %d], AOE: %d",pers->id,target->id,eff->aoeCnt);
	fs_fightSaveLog(pers,FS_FLC_EFFECTUSE,eff->id,0,0,eff->title,true);

	v_init(&v1);
	v_push(&v1,target);
	if (eff->aoeCnt > 0 && !(eff->flags & FS_PEF_AURA)) {	// AOE effect and not aura! WARNING!! ATTENTION!
		v_init(&v2);
		fs_fightLockMutex(pers->fight);
		v_reset(pers->fight->persVec,&vi);
		while ((p = v_each(pers->fight->persVec,&vi))) {
			if ((p == target) || (p->teamNum != target->teamNum)) continue;
			if ((p->status != FS_PS_FIGHTING) && (p->status != FS_PS_ACTIVE) && (p->status != FS_PS_PASSIVE)) continue;
			if (fs___persEffectTargetCheck(pers,eff,p,&i) != OK) continue;
			v_push(&v2,p);
		}
		fs_fightUnlockMutex(pers->fight);

/*		// random equal pickup
		for (i = 0; (i < eff->aoeCnt) && v_size(&v2); i++) {
			idx = randInt(0,v_size(&v2)-1,NULL);
			p = v_elem(&v2,idx);
			v_remove_at(&v2,idx,0);
			v_push(&v1,p);
			DEBUG(" * AOE target id: %d",p->id);
		}
*/
		// random weight-wise pickup
		for (i = 0; (i < eff->aoeCnt) && v_size(&v2); i++) {
			aoeWeight = 0;
			v_reset(&v2,0);
			while ((p = v_each(&v2,0))) aoeWeight += fs___persAoeWeight(p, target);
			aoeRand = randDouble(0, aoeWeight, NULL);
			aoeWeight = idx = 0;
			v_reset(&v2,0);
			while ((p = v_each(&v2,0))) {
				aoeWeight += fs___persAoeWeight(p, target);
				if (aoeRand < aoeWeight) {
					v_remove_at(&v2,idx,0);
					v_push(&v1,p);
					break;
				}
				++idx;
			}
		}
		v_zero(&v2);
	}
	effUsageStatuses = malloc(sizeof(int)*v_size(&v1));
	idx = 0;
	v_reset(&v1,0);
	while ((p = v_each(&v1,0))) {
		fs___persEffectTargetUpdate(pers,eff,p);
		i = 0;
		if (eff->dmgType & p->ImmunDmgType) i = 10;	// 10 - immunity
		else if (!randRoll(fs_persHitProb(pers,p,eff->dmgType),NULL)) i = 20;	// 20 - magic hit chance roll failed
		else if (!(eff->flags & FS_PEF_NOFULLRESIST) && randRoll(fs_persResistProb(pers,p,eff->dmgType),NULL)) i = 30;	// 30 - full resist
		effUsageStatuses[idx++] = i;
		if (PERS_OPP_ID(pers) && (pers->opponent == p)) *usageStatus = i;
	}

	// setting events
	p = PERS_OPP_ID(pers) && (v_search(&v1,pers->opponent) != -1) ? pers->opponent : target;
	pAnim = p;
	if(eff->flags & FS_PEF_ANIMINVERT){
		do{
			if(!PERS_OPP_ID(pers)) break;
			DEBUG("PERS ANIM %d : %d", pers->id, pAnim->id);
			if(pers->opponent == pAnim){
				pAnim = pers;
				DEBUG("PERS ANIM1 %d : %d", pers->id, pAnim->id);
			}else{
				pAnim = pers->opponent;
				DEBUG("PERS ANIM2 %d : %d", pers->id, pAnim->id);
			}
		}while(0);
	}
	if (!auxCall) {
		if(eff->flags & FS_PEF_ANIMINVERT){
			fs_persSetEvent(pers,FS_PE_EFFECTUSE,"iisi",pers->id,pAnim->id,eff->animData,*usageStatus);
		}else{
			fs_persSetEvent(pers,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData,*usageStatus);
		}
		/*fs_persSetEvent(pers,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData,*usageStatus);*/
		if (PERS_OPP_ID(pers) && (pers->opponent != p)){
			if(eff->flags & FS_PEF_ANIMINVERT){
				fs_persSetEvent(pers->opponent,FS_PE_EFFECTUSE,"iisi",pers->id,pAnim->id,eff->animData,*usageStatus);
			}else{
				fs_persSetEvent(pers->opponent,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData,*usageStatus);
			}
		}
	}

	bool auraEffHave = false;
	v_reset(pers->effVec,0);
    while ((auraEff = v_each(pers->effVec,0))) {
        if (!(auraEff->flags & FS_PEF_ACTIVE) || !(auraEff->flags & FS_PEF_AURA)) continue;
        if (auraEff->flags & FS_PEF_AURA) break;
    }
	if(auraEff && (auraEff->flags & FS_PEF_AURA) && (auraEff->flags & FS_PEF_ACTIVE)){
		auraEffHave = true;
	}

	idx = 0;
	v_reset(&v1,0);
	while ((p = v_each(&v1,0))) {
		i = effUsageStatuses[idx++];
		// setting events
		if (!auxCall && (p != pers)) {
			fs_persSetEvent(p,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData,i);
			if (PERS_OPP_ID(p) && (p->opponent != pers)) fs_persSetEvent(p->opponent,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData,i);
		}
		dmg = -1;
		if ((i == 0) && !eff->probAuto && (!eff->prob || randRoll(eff->prob,NULL))) { // add && !eff->probAuto
			fs___persActivateEffect(pers,eff,p);
		}
		if (((i == 0) || (i == 20) || (i == 30)) && (eff->dmg > 0)) {	// applying unconditional effect damage
			if ((i == 0) || (i == 30)) {
				fs_persGetCharge(pers,eff->dmgType,&charge, false);
				dmg = fs_persRecalcDmg(pers,p,eff->dmgType,eff->dmg,0,eff->aoeCnt);
				dmg += dmg * charge.dmgX;	// dmgX
				//Если персонаж в магической стойке, а эффект является спеллом, а еще оппонент в блоке, то резать урон!

				//Снижение магического урона в блоке
				dmgDxPlus = 0.0;
				dmgDxPlusInt = PERS_SKILL(p, FS_SK_PCRSTAIRBLOCK);
				if(dmgDxPlusInt > 0) dmgDxPlus = dmgDxPlusInt / 100.0;

				if(auraEffHave && pers->flags & FS_PF_MAGIC && eff->flags & FS_PEF_SPELL && p->flags & FS_PF_DEFENDED) {
					dmg *= _dmgDx - dmgDxPlus;
				}
				dmg = fs_persDamage(p,dmg,eff->dmgType,randRoll(charge.critProb,NULL),pers);

				if(charge.vamp > 0) { //Восставовление от вампирика
					_chargeVampInt = PERS_SKILL(pers,FS_SK_CHVAMPPLUS);
					if(_chargeVampInt > 0) _chargeVamp = _chargeVampInt / 100.0;
				}else if(charge.vamp < 0){ //Урон от вампирика
					_chargeVampInt = PERS_SKILL(pers,FS_SK_CHVAMPMINUS);
					if(_chargeVampInt > 0) _chargeVamp = _chargeVampInt / 100.0;
					if(_chargeVamp > charge.vamp) charge.vamp = 0;
				}

				fs_persDamage(pers,-dmg*(charge.vamp+_chargeVamp),0,false,NULL);	// vampiric
				fs_persRecalcEffects(p);
				fs_persRecalcEffects(pers);
				if (randRoll(charge.stunProb,NULL) && !(pers->flags & FS_PF_STUNNED)) fs_persStun(pers,p,charge.stunTime, charge.stunCnt);	// stun
			}
			if(!(eff->flags & FS_PEF_SPELL && pers->flags & FS_PF_MAGIC)) { //Если это не спелл, снимаем chargy, а если спелл, то снимаем все в другом месте, нужно выйти из while
				fs_persDischarge(pers,eff->dmgType);
			}
		}

		// fight log events
		fs_persSetEvent(pers,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,eff->id,(int)dmg,0,eff->title);
		if (PERS_OPP_ID(pers) && (pers->opponent != p)) fs_persSetEvent(pers->opponent,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,eff->id,(int)dmg,0,eff->title);
		if (p != pers) {
			fs_persSetEvent(p,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,eff->id,(int)dmg,0,eff->title);
			if (PERS_OPP_ID(p) && (p->opponent != pers)) fs_persSetEvent(p->opponent,FS_PE_FIGHTLOG,"iiiiiiis",fs_stime,pers->id,p->id,FS_FLC_EFFECTUSE,eff->id,(int)dmg,0,eff->title);
		}
	}

	//set Event for bow
	if(eff->flags & FS_PEF_BOW){
		fs_persSetEvent(pers, FS_PE_ENERGYCONSUM,"ii",pers->id,eff->e_yarost);
		fs_persSetEvent(pers, FS_PE_ARROWCONSUM,"ii",pers->id,1); // one bow
	}
	
	free(effUsageStatuses);
	v_zero(&v1);

	if ((eff->flags & FS_PEF_PASSTURN) && (pers->status == FS_PS_ACTIVE)) {
		pers->timeoutCnt = 0;
		fs_persGetTurn(pers->opponent);
	}
	if (!(eff->flags & FS_PEF_SPELL)) eff->cnt--;
	
	if(eff->flags & FS_PEF_SPELL && pers->flags & FS_PF_MAGIC && eff->flags & FS_PEF_PASSTURN) {
		fs_persAddYarost(pers, 3); //add Yarost
		fs_persDischarge(pers,-1); //discharge ANYTHINK!
		pers->userUdarCnt++; //FIX Ударов для каста петов!

		//Обработчик кол-ва ударов станов
		if(pers->opponent) {
			if(pers->opponent->stunCnt > 0) pers->opponent->stunCnt--;
			if(pers->opponent->stunSafeCnt > 0) pers->opponent->stunSafeCnt--;
		}
	}
	
	//Убираем вариативки
	if(!(eff->flags & FS_PEF_SPELL || eff->flags & FS_PEF_ACTIVE || eff->flags & FS_PEF_AUX)) {
	v_reset(pers->effVec,0);
	while ((effS = v_each(pers->effVec,0))) {
		if(effS->subSlot > 1 && effS->slotNum == eff->slotNum){
			v_remove(pers->effVec,effS);
			fs_persEffDelete(effS);
		}
	}
	}
	
	//убираем другие ауры
	if((eff->flags & FS_PEF_AURA)){
		v_reset(pers->effVec,0);
		while ((effS = v_each(pers->effVec,0))) {
			if((effS->flags & FS_PEF_AURA) && eff->id != effS->id && (effS->flags & FS_PEF_ACTIVE)){
				effS->flags |= FS_PEF_DROP;
			}
		}
	}
	
	
	pers->lastEffectUpdateIndex++;
	
	return OK;
}

errno_t fs___persEffectTargetCheck(fs_pers_t *pers, fs_persEff_t *eff, fs_pers_t *target, int *usageStatus) {
	fs_persEff_t *effCopy;
	
	*usageStatus = 0;
	if ((eff->flags & FS_PEF_TARGETNOTUSER) && !(target->flags & FS_PF_BOT)) return ERR_GENERAL;
	if ((eff->flags & FS_PEF_TARGETNOTBOT) && (target->flags & FS_PF_BOT)) return ERR_GENERAL;
	if ((eff->code == FS_PEC_RESURRECT) && (target->status != FS_PS_DEAD)) return ERR_GENERAL;

	// checking effect group
	if (eff->flags & (FS_PEF_GRPDENY | FS_PEF_CDGRPDENY)) {
		v_reset(target->effVec,0);
		while ((effCopy = v_each(target->effVec,0))) {
			if (!(effCopy->flags & FS_PEF_ACTIVE)) continue;
			if ((eff->flags & FS_PEF_GRPDENY) && (effCopy->grpId == eff->grpId)) {	// group IDs match
				*usageStatus = -30;	// -30 - denied by group ID
				return ERR_GENERAL;
			} else
			if ((eff->flags & FS_PEF_CDGRPDENY) && (effCopy->cdGrpId == eff->cdGrpId)) {	// cooldown group IDs match
				*usageStatus = -30;	// -30 - denied by group ID
				return ERR_GENERAL;
			}
		}
	}
	return OK;
}

void fs___persEffectTargetUpdate(fs_pers_t *pers, fs_persEff_t *eff, fs_pers_t *target) {
	fs_persEff_t *effCopy;

	// dropping effect group
	if (eff->flags & (FS_PEF_GRPDROP | FS_PEF_CDGRPDROP)) {
		v_reset(target->effVec,0);
		while ((effCopy = v_each(target->effVec,0))) {
			if (!(effCopy->flags & FS_PEF_ACTIVE)) continue;
			if ((eff->flags & FS_PEF_GRPDROP) && (effCopy->grpId == eff->grpId)) {	// group IDs match
				effCopy->flags |= FS_PEF_DROP;
			} else
			if ((eff->flags & FS_PEF_CDGRPDROP) && (effCopy->cdGrpId == eff->cdGrpId)) {	// cooldown group IDs match
				effCopy->flags |= FS_PEF_DROP;
			}
		}
	}
}

void fs___persActivateEffect(fs_pers_t *pers, fs_persEff_t *eff, fs_pers_t *target) {
	fs_persEff_t *effCopy;
	
	effCopy = fs_persEffCopy(eff);
	if (!effCopy) return;
	effCopy->flags |= FS_PEF_ACTIVE;
	effCopy->activator = pers;
	if ((effCopy->flags & FS_PEF_CHARGE) && (effCopy->actTime <= 0)) {
		effCopy->actTime = 1;
		target = pers;
	}
	v_push(target->effVec,effCopy);
	fs_persRecalcEffects(target);
}

inline double fs___persAoeWeight(fs_pers_t *pers, fs_pers_t *target) {
	double x = pers->level - target->level;
	return pow(2.0, -(x * x) / 4.0);
}

errno_t fs_persRecalcEffects(fs_pers_t *pers) {
	fs_pers_t 		  *target;
	fs_persEff_t      *eff;
	fs_skill_t        skill;
	fs_skillVal_t     val;
	fs_skillVal_t     val2;
	fs_skillVal_t     my_hp;
	fs_skillVal_t     tg_hp;
	fs_skillArr_t     tmpSkills;
	viter_t           vi;
	int               stime, ticks, i;
	bool              fTime, fPeriod, fStart, fEnd, fTrigger;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	memset(tmpSkills,0,sizeof(fs_skillArr_t));
	stime = fs_stime;
	v_reset(pers->effVec,&vi);
	while ((eff = v_current(pers->effVec,&vi))) {
		if (!(eff->flags & FS_PEF_ACTIVE)) {
			v_next(pers->effVec,&vi);
			continue;
		}
		ticks = 0;
		fTime = fPeriod = fStart = fEnd = fTrigger = false;
		fTime = (eff->actTime > 0);
		fPeriod = fTime && (eff->actPeriod > 0);
		if (!eff->estime) {
			fStart = true;
			eff->estime = stime;
			eff->eetime = stime + eff->actTime;
		}
		fEnd = (eff->eetime <= stime) || (eff->flags & FS_PEF_DROP);
		if (fPeriod) {
			ticks = eff->actTime/eff->actPeriod;
			if (!eff->eptime) eff->eptime = stime + eff->actPeriod - 1;	// switching the time shifter to the right of the interval
			if (eff->eptime <= stime) {
				fTrigger = true;
				eff->eptime = stime + eff->actPeriod;
			}
		}
		DEBUG("* RECALC (persId: %d) [effId: %d, effCode: %d, ticks: %d, fTime: %d, fPeriod: %d, fStart: %d, fEnd: %d, fTrigger: %d]", pers->id, eff->id, eff->code, ticks, fTime, fPeriod, fStart, fEnd, fTrigger);
		switch (eff->code) {
			case FS_PEC_ADDSKILLS:
				if (fStart && eff->i1) {	// start-time percent skill recalc
					for (skill=0; skill<=FS_SK_MAXCODE; skill++) {
						val = (PERS_INTSKILL(pers,skill) * eff->skills[skill])/100;
						if (eff->i2 > 0) val = MAX(MIN(val,eff->i2),-eff->i2);	// max value
						eff->skills[skill] = val;
					}
					eff->skills[FS_SK_XHPMAX] += eff->skills[FS_SK_HPMAX];
					eff->skills[FS_SK_XMPMAX] += eff->skills[FS_SK_MPMAX];
				}
				// recalculating magic damage
				if ((eff->dmg <= 0) && (eff->dmgType & ~OLD_DMGTYPE) && fStart && fPeriod && (eff->skills[FS_SK_HP] < 0) && eff->activator && (ticks > 0)) {
					eff->dmgRecalc = fs_persRecalcDmg(eff->activator,pers,eff->dmgType,(-eff->skills[FS_SK_HP] * ticks),1,eff->aoeCnt) / ticks;
					eff->dmgRecalc = MAX(eff->dmgRecalc, 0.1);
				}
				for (skill = 0; skill<=FS_SK_MAXCODE; skill++) {
					val = eff->skills[skill];
					if ((!fTime && fStart) || (fPeriod && fTrigger)) {
						if (skill == FS_SK_HP) {
							fs_persDamage(pers,(eff->dmgRecalc ? eff->dmgRecalc : -val),eff->dmgType,false,eff->activator);
						} else if (skill == FS_SK_MP) {
							fs_persConsumeManna(pers,-val,!eff->id);
						} else {
							PERS_INTSKILL(pers,skill) += val;
						}	
					} else if (fTime && !fPeriod && !fEnd) tmpSkills[skill] += val;
				}
				break;
			case FS_PEC_ADDHP:
				if ((!fTime && fStart) || (fPeriod && fTrigger)) {
					/*
					target = eff->activator;
					if(target){						
						val = PERS_HPMAX(pers);
						val2 = PERS_HPMAX(target);					
						if (eff->i3 > 0)
						{ 
							val = (val <= val2 ? PERS_HPMAX(pers) * eff->f1 : PERS_HPMAX(target) * eff->f1);
						}else{
							val = PERS_HPMAX(pers) * eff->f1;
						}					
					}else{
						val = PERS_HPMAX(pers) * eff->f1;
					}
					*/
					double percent_hpmod = (double)PERS_EXTSKILL(pers,FS_SK_HPMOD) / 100.0 / ticks;
					double percent_unhpmod = (double)PERS_EXTSKILL(pers,FS_SK_UNHPMOD) / 100.0 / ticks;
					if(percent_hpmod < 0) percent_unhpmod = 0;
					if(percent_unhpmod < 0) percent_unhpmod = 0;
					if(percent_unhpmod) percent_hpmod -= percent_unhpmod; //Ослабление идет
					val = PERS_HPMAX(pers) * (eff->f1 + percent_hpmod); //ADD HP IN HPMOD
					if (eff->i3 > 0 && eff->activator) { //Костыль
						my_hp = PERS_HPMAX(pers);
						tg_hp = PERS_HPMAX(eff->activator);	
						val = (my_hp <= tg_hp ? PERS_HPMAX(pers) * eff->f1 : PERS_HPMAX(eff->activator) * eff->f1);
					}
					if (eff->i2 > 0) val = MAX(MIN(val,eff->i2),-eff->i2);	// max value
					// recalculating magic damage
					if ((eff->dmg <= 0) && (eff->dmgType & ~OLD_DMGTYPE) && fStart && fPeriod && (val < 0) && eff->activator && (ticks > 0)) {
						eff->dmgRecalc = fs_persRecalcDmg(eff->activator,pers,eff->dmgType,(-val * ticks),1,eff->aoeCnt) / ticks;
						eff->dmgRecalc = MAX(eff->dmgRecalc, 0.1);
					}
					fs_persDamage(pers,(eff->dmgRecalc ? eff->dmgRecalc : -val),eff->dmgType,false,eff->activator);
				}
				break;
			case FS_PEC_MODHP:
				if(eff->i3){
					val = PERS_HPMAX(pers) * (eff->f1); //Берем у себя хп
				}else{
					if(!pers->opponent) break; //Нет оппонента нет эффекта.
					val = PERS_HPMAX(pers->opponent) * (eff->f1); //Берем у оппонента
				}
				if (eff->i2 > 0) val = MAX(MIN(val,eff->i2),-eff->i2);	// max value
				fs_persDamage(pers,-val,eff->dmgType,false,eff->activator);
				break;
			case FS_PEC_ADDMP:
				if ((!fTime && fStart) || (fPeriod && fTrigger)) {
					val = PERS_MPMAX(pers) * eff->f1;
					if (eff->i2 > 0) val = MAX(MIN(val,eff->i2),-eff->i2);	// max value
					fs_persConsumeManna(pers,-val,!eff->id);
				}
				break;
			case FS_PEC_STUN:
				if (randRoll(eff->f1,NULL)) fEnd = true;
				if (fStart && !(fEnd && (pers->stunTime <= eff->eetime))) {
					pers->flags |= FS_PF_STUNNED;
					pers->stunTime = MAX(pers->stunTime,eff->eetime);
					if (pers->status == FS_PS_ACTIVE) fs_persGetTurn(pers->opponent);
				}
				if (fEnd && (pers->stunTime <= eff->eetime)) {
					pers->flags &= ~FS_PF_STUNNED;
					pers->stunTime = 0;
					pers->stunSafeCnt = 2; //Ставим защиту, что 2 следующих удара никаким образом не будут станами
					if(pers->opponent) fs_persGetTurn(pers->opponent);
				}
				/*
				else if(pers->stunCnt <= 0){ //Если на персонажа было покушение и ударов сделали в стане уже слишком много... снимаем стан
					pers->flags &= ~FS_PF_STUNNED;
					pers->stunTime = 0;
					pers->stunSafeCnt = 2; //Ставим защиту, что 2 следующих удара никаким образом не будут станами
					if(pers->opponent) fs_persGetTurn(pers->opponent);
					fEnd = true;
				}
				*/
				break;
			case FS_PEC_RESURRECT:
				if (!fStart) break;
				if (pers->status != FS_PS_DEAD) break;
				/*pers->status = FS_PS_FIGHTING;
				pers->mtime = 0;
				fs_persDamage(pers,(-PERS_HPMAX(pers) * eff->f1),0,false,eff->activator);
				fs_persSetEvent(pers, FS_PE_MYFIGHTRETURN, 0);
				fs_persSetEvent(pers, FS_PE_ATTACKWAIT, 0);
				
				pers->new_pers_id = 0;
				
				//pers ressurecter
				if (!((pers->flags & FS_PF_LIFELESS) || (PERS_HP(pers) <= 0))){
					fs_fightLockMutex(pers->fight);
					fs_fightDeadCnt(pers->fight, pers->teamNum, -1);
					fs_fightUnlockMutex(pers->fight);
				}*/
				
				pers->status = FS_PS_FIGHTING;
				pers->new_pers_id = 0; //
				pers->mtime = 0;
				fs_persDamage(pers,(-PERS_HPMAX(pers) * eff->f1),0,false,eff->activator);
				fs_persSetEvent(pers, FS_PE_MYFIGHTRETURN,0); 
 				fs_persSetEvent(pers,FS_PE_ATTACKWAIT,0);
				//fs_fightLockMutex(pers->fight); //fight ready locked
				fs_fightDeadCnt(pers->fight,pers->teamNum,-1);	
				//fs_fightUnlockMutex(pers->fight); //fight ready locked
				
				break;
			case FS_PEC_BOTHELP:
				if (!fStart) break;
				fs_feedbackData("siii","BOT_HELP",pers->fight->id,pers->id,eff->i1);
				break;
			default:
				break;
		}
		i = fTime ? fStart ? 1: fEnd ? -1: 0: 0;

		if (eff->picture && (eff->flags & FS_PEF_AURA)) { //FIX AURA.. HZ
			fs_persSetEvent(pers,FS_PE_EFFECTAPPLY,"iiisssii",pers->id,eff->artId,i,eff->title,eff->picture,eff->animData,eff->eetime,(eff->turnsLeft ? eff->turnsLeft : 0)); //TODO: ADD PARAM i = turnsLeft(int)
			if (PERS_OPP_ID(pers)) fs_persSetEvent(pers->opponent,FS_PE_EFFECTAPPLY,"iiisssii",pers->id,eff->artId,i,eff->title,eff->picture,eff->animData,eff->eetime,(eff->turnsLeft ? eff->turnsLeft : 0));
		}else if (eff->picture && i) {
			fs_persSetEvent(pers,FS_PE_EFFECTAPPLY,"iiisssii",pers->id,eff->artId,i,eff->title,eff->picture,eff->animData,eff->eetime,(eff->turnsLeft ? eff->turnsLeft : 0)); //TODO: ADD PARAM i = turnsLeft(int)
			if (PERS_OPP_ID(pers)) fs_persSetEvent(pers->opponent,FS_PE_EFFECTAPPLY,"iiisssii",pers->id,eff->artId,i,eff->title,eff->picture,eff->animData,eff->eetime,(eff->turnsLeft ? eff->turnsLeft : 0));
		}
		if (fEnd) {
			v_remove_at(pers->effVec,v_idx(pers->effVec,&vi),&vi);
			fs_persEffDelete(eff);
			if(eff->flags & FS_PEF_AURA){
				//NOTHINK!
			}else{
				
			}
		} else v_next(pers->effVec,&vi);
	}
	for (skill = 0; skill<=FS_SK_MAXCODE; skill++) {
		PERS_EXTSKILL(pers,skill) = PERS_INTSKILL(pers,skill) + tmpSkills[skill];
		if ((skill != FS_SK_XHPMAX) && (skill != FS_SK_XMPMAX)) PERS_EXTSKILL(pers,skill) = MAX(PERS_EXTSKILL(pers,skill), 0);
	}
	if (pers->status == FS_PS_DEAD) PERS_INTSKILL(pers,FS_SK_HP) = PERS_EXTSKILL(pers,FS_SK_HP) = 0;

	// HPMAX calc
	PERS_INTSKILL(pers,FS_SK_HPMAX) = round((PERS_INTSKILL(pers,FS_SK_VIT) + _COMP(PERS_LEVEL(pers),_V0)*(1 - _IComp)) * _Vs * (pers->art ? 1: _decHP)) + PERS_INTSKILL(pers,FS_SK_XHPMAX);
	PERS_EXTSKILL(pers,FS_SK_HPMAX) = round((PERS_EXTSKILL(pers,FS_SK_VIT) + _COMP(PERS_LEVEL(pers),_V0)*(1 - _IComp)) * _Vs * (pers->art ? 1: _decHP)) + PERS_EXTSKILL(pers,FS_SK_XHPMAX);
	// HPMAX can't be less than 0
	PERS_INTSKILL(pers,FS_SK_HPMAX) = MAX(PERS_INTSKILL(pers,FS_SK_HPMAX), 0);
	PERS_EXTSKILL(pers,FS_SK_HPMAX) = MAX(PERS_EXTSKILL(pers,FS_SK_HPMAX), 0);
	// HP appropriate shift
	PERS_INTSKILL(pers,FS_SK_HP) -= MAX(PERS_HP(pers) - PERS_HPMAX(pers), 0);
	PERS_EXTSKILL(pers,FS_SK_HP) -= MAX(PERS_HP(pers) - PERS_HPMAX(pers), 0);
	// MPMAX calc
	PERS_INTSKILL(pers,FS_SK_MPMAX) = PERS_INTSKILL(pers,FS_SK_INTELL) + PERS_INTSKILL(pers,FS_SK_XMPMAX);
	PERS_EXTSKILL(pers,FS_SK_MPMAX) = PERS_EXTSKILL(pers,FS_SK_INTELL) + PERS_EXTSKILL(pers,FS_SK_XMPMAX);
	// MPMAX can't be less than 0
	PERS_INTSKILL(pers,FS_SK_MPMAX) = MAX(PERS_INTSKILL(pers,FS_SK_MPMAX), 0);
	PERS_EXTSKILL(pers,FS_SK_MPMAX) = MAX(PERS_EXTSKILL(pers,FS_SK_MPMAX), 0);
	// MP appropriate shift
	PERS_INTSKILL(pers,FS_SK_MP) -= MAX(PERS_MP(pers) - PERS_MPMAX(pers), 0);
	PERS_EXTSKILL(pers,FS_SK_MP) -= MAX(PERS_MP(pers) - PERS_MPMAX(pers), 0);

	if ((pers->flags & FS_PF_LIFELESS) || (PERS_HP(pers) <= 0)) fs_persDie(pers,NULL);
	return OK;
}

errno_t fs_persDropEffects(fs_pers_t *pers) {
	fs_persEff_t *eff;
	viter_t      vi;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	v_reset(pers->effVec,&vi);
	while ((eff = v_each(pers->effVec,&vi))) {
		if (!(eff->flags & FS_PEF_ACTIVE)) continue;
		eff->flags |= FS_PEF_DROP;
	}
	fs_persRecalcEffects(pers);
	return OK;
}

errno_t fs_persGetCharge(fs_pers_t *pers, int dmgType, fs_persCharge_t *charge, bool moveCnt) {
	fs_persEff_t *eff;
	viter_t      vi;

	if (!pers || !charge) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	memset(charge,0,sizeof(fs_persCharge_t));
	if (!dmgType) return OK;
	v_reset(pers->effVec,&vi);
	while ((eff = v_each(pers->effVec,&vi))) {
		if (!(eff->flags & FS_PEF_ACTIVE) || !(eff->dmgType & dmgType)) continue;
		charge->dmgX += eff->skills[FS_SK_CHRGDMGX] / 100.0;
		charge->vamp += eff->skills[FS_SK_CHRGVAMP] / 100.0;
		charge->stunProb += eff->skills[FS_SK_CHRGSTUNP] / 100.0;
		charge->stunTime = MAX(charge->stunTime,eff->skills[FS_SK_CHRGSTUNT]);
		charge->critProb += eff->skills[FS_SK_CHRGCRIT] / 100.0;

charge->stunCnt += eff->skills[FS_SK_CHRGSTUNC];
		charge->cnt += eff->actMoveCnt;

		//Модификаторы пелены с эффектов
		charge->aura_dmgX += eff->skills[FS_SK_AURACHRGDMGX] / 100.0;
		charge->aura_vamp += eff->skills[FS_SK_AURACHRGVAMP] / 100.0;
		charge->aura_critProb += eff->skills[FS_SK_AURACHRGCRIT] / 100.0;

		charge->razpeleny += eff->skills[FS_SK_RAZPELENY];
		//charge->cnt += eff->actMoveCnt;
		//if(eff->actMoveCnt) eff->actMoveCnt--;
	}

	//Модификаторы пелены со скиллов перса
	charge->aura_dmgX += PERS_EXTSKILL(pers,FS_SK_AURACHRGDMGX) / 100.0;
	charge->aura_vamp += PERS_EXTSKILL(pers,FS_SK_AURACHRGVAMP) / 100.0;
	charge->aura_critProb += PERS_EXTSKILL(pers,FS_SK_AURACHRGCRIT) / 100.0;

	//DEBUGXXX("chargam (%d) (%d) (%d) (%.2f)", pers->id, PERS_INTSKILL(pers,FS_SK_AURACHRGCRIT), PERS_EXTSKILL(pers,FS_SK_AURACHRGCRIT),charge->aura_critProb);

	DEBUG("(%d) [dmgX: %.2f, vamp: %.2f, stunProb: %.2f, stunTime: %d, critProb: %.2f]", pers->id, charge->dmgX, charge->vamp, charge->stunProb, charge->stunTime, charge->critProb);
	return OK;
}

errno_t fs_persDischarge(fs_pers_t *pers, int dmgType) {
	fs_persEff_t *eff;
	viter_t      vi;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	v_reset(pers->effVec,&vi);
	/*while ((eff = v_each(pers->effVec,&vi))) {
		if (!(eff->flags & FS_PEF_ACTIVE) || !(eff->flags & FS_PEF_CHARGE) || !(eff->dmgType & dmgType) || eff->flags & FS_PEF_AURA) continue;
		eff->flags |= FS_PEF_DROP;
		DEBUG("drop (%d) [%d]", pers->id, eff->id);
	}*/
	while ((eff = v_each(pers->effVec,&vi))) {
		if (!(eff->flags & FS_PEF_ACTIVE) || !(eff->flags & FS_PEF_CHARGE) || eff->flags & FS_PEF_AURA) continue;
		if(dmgType != -1 && !(eff->dmgType & dmgType)) continue;
		if(eff->actMoveCnt <= 1){
			eff->flags |= FS_PEF_DROP;
			DEBUG("drop (%d) [%d]", pers->id, eff->id);
		}else{
			eff->actMoveCnt--;
		}
	}
	fs_persRecalcEffects(pers);
	return OK;
}

errno_t fs_persAddComboItem(fs_pers_t *pers, int item) {
	int     i;

	if (!pers) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (pers->cmbSize < MAX_COMBO_SIZE) {
		pers->cmbSeq[pers->cmbSize] = item;
		pers->cmbSize++;
	} else {
		for (i=1; i<MAX_COMBO_SIZE; i++) pers->cmbSeq[i-1] = pers->cmbSeq[i];
		pers->cmbSeq[i-1] = item;
	}
	return OK;
}

fs_persCmb_t *fs_persCheckCombo(fs_pers_t *pers) {
	fs_persCmb_t *cmb;
	int          i;
	bool         f;

	if (!pers) {
		WARN("Invalid arguments");
		return NULL;
	}
	v_reset(pers->cmbVec,0);
	while ((cmb = v_each(pers->cmbVec,0))) {
		if ((cmb->size <= 0) || (cmb->size > pers->cmbSize)) continue;
		f = true;
		for (i=0; i<cmb->size; i++) {
			if (cmb->seq[i] != pers->cmbSeq[pers->cmbSize - cmb->size + i]) {
				f = false;
				break;
			}
		}
		if (f) break;
	}
	return cmb;
}

errno_t fs_followPersDelete(fs_followPers_t *follow) {
	if (!follow) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	free(follow->pers);
	free(follow);
	return OK;
}


errno_t fs___persActivateEffectCheck(fs_pers_t *pers, fs_persEff_t *eff, fs_pers_t *target) {
	fs_persEff_t *effCopy;
	fs_persEff_t *eff2;
	bool active = false;
	fs_pers_t *pAnim, *p;
	vector_t          v1; //MultiAttack Personages
	
	if(!pers || !eff) return OK;
	
	//Проверяем активные эффекты, если есть похожий то не активируем богов!
	bool activate_eff = true;
	if(!pers || !target) return OK;
	//Проверяем активные эффекты, если есть похожий то не активируем богов!
	v_reset(target->effVec,0);
	while ((eff2 = v_each(target->effVec,0))) {
		if((eff2->artId == eff->artId) && eff2->flags & FS_PEF_ACTIVE) {
			activate_eff = false;
			break;
		}
		if(eff2->cdGrpId && (eff2->cdGrpId == eff->cdGrpId) && eff2->flags & FS_PEF_ACTIVE) {
			activate_eff = false;
			break;
		}
	}
	if(!activate_eff) return OK;

	
	if (fs_persUseEffect(pers,eff,target,NULL) == OK) {
		if((eff->flags & FS_PEF_SPELL) && eff->cnt > 0) eff->cnt--;
	}
	
	/*
	// setting events
	p = PERS_OPP_ID(pers) ? pers->opponent : target;
	pAnim = p;
	if(eff->flags & FS_PEF_ANIMINVERT){
		do{
			if(!PERS_OPP_ID(pers)) break;
			if(pers->opponent == pAnim){ pAnim = pers; }else{ pAnim = pers->opponent; }
		}while(0);
	}
	if(eff->flags & FS_PEF_ANIMINVERT){
		fs_persSetEvent(pers,FS_PE_EFFECTUSE,"iisi",pers->id,pAnim->id,eff->animData, 0);
	}else{
		fs_persSetEvent(pers,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData, 0);
	}
	if (PERS_OPP_ID(pers) && (pers->opponent != p)){
		if(eff->flags & FS_PEF_ANIMINVERT){
			fs_persSetEvent(pers->opponent,FS_PE_EFFECTUSE,"iisi",pers->id,pAnim->id,eff->animData, 0);
		}else{
			fs_persSetEvent(pers->opponent,FS_PE_EFFECTUSE,"iisi",pers->id,p->id,eff->animData, 0);
		}
	}
	*/
}