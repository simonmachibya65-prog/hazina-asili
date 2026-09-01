USE `natural_compounds_db`;

INSERT IGNORE INTO `organisms` (`kingdom`, `phylum`, `class`, `scientific_name`) VALUES
('Plantae','Tracheophyta','Magnoliopsida','Taxus brevifolia'),
('Plantae','Tracheophyta','Magnoliopsida','Cinchona officinalis'),
('Plantae','Tracheophyta','Magnoliopsida','Papaver somniferum'),
('Plantae','Tracheophyta','Magnoliopsida','Catharanthus roseus'),
('Plantae','Tracheophyta','Magnoliopsida','Artemisia annua'),
('Plantae','Tracheophyta','Magnoliopsida','Camptotheca acuminata'),
('Plantae','Tracheophyta','Magnoliopsida','Glycyrrhiza glabra'),
('Plantae','Tracheophyta','Magnoliopsida','Vitis vinifera'),
('Plantae','Tracheophyta','Magnoliopsida','Piper nigrum'),
('Plantae','Tracheophyta','Magnoliopsida','Capsicum annuum'),
('Plantae','Tracheophyta','Magnoliopsida','Ephedra sinica'),
('Fungi','Basidiomycota','Agaricomycetes','Ganoderma lucidum'),
('Bacteria','Actinobacteria','Actinomycetia','Streptomyces avermitilis');

INSERT INTO `compounds` (`name`,`formula`,`molecular_weight`,`description`,`organism_id`,`created_by`,`version`) VALUES
('Paclitaxel (Taxol)','C47H51NO14',853.9060,'A diterpenoid natural product isolated from the Pacific yew tree. One of the most important anticancer drugs — it stabilizes microtubules and prevents cell division. Used clinically to treat breast, ovarian, and lung cancers.',(SELECT id FROM organisms WHERE scientific_name='Taxus brevifolia'),1,1),
('Quinine','C20H24N2O2',324.4170,'An alkaloid found in the bark of Cinchona trees. The first effective treatment for malaria, it acts by interfering with the parasite ability to digest hemoglobin. Also used as a flavoring agent in tonic water.',(SELECT id FROM organisms WHERE scientific_name='Cinchona officinalis'),1,1),
('Morphine','C17H19NO3',285.3380,'A potent opioid alkaloid found in the opium poppy. The primary active agent in opium, it acts on opioid receptors in the central nervous system to relieve pain. It is the prototype of all opioid analgesics.',(SELECT id FROM organisms WHERE scientific_name='Papaver somniferum'),1,1),
('Vincristine','C46H56N4O10',824.9580,'A vinca alkaloid derived from the Madagascar periwinkle. It inhibits mitosis by binding to tubulin and preventing microtubule formation. Widely used in chemotherapy for leukemia and lymphoma.',(SELECT id FROM organisms WHERE scientific_name='Catharanthus roseus'),1,1),
('Artemisinin','C15H22O5',282.3320,'A sesquiterpene lactone endoperoxide isolated from Artemisia annua (sweet wormwood). The most effective antimalarial drug available, particularly against drug-resistant Plasmodium falciparum. Nobel Prize in Medicine 2015.',(SELECT id FROM organisms WHERE scientific_name='Artemisia annua'),1,1),
('Camptothecin','C20H16N2O4',348.3530,'A cytotoxic quinoline alkaloid isolated from Camptotheca acuminata. It inhibits DNA topoisomerase I, preventing DNA replication. Parent compound of anticancer drugs irinotecan and topotecan.',(SELECT id FROM organisms WHERE scientific_name='Camptotheca acuminata'),1,1),
('Glycyrrhizin','C42H62O16',822.9300,'A triterpenoid saponin and the primary sweet-tasting compound in licorice root. It has anti-inflammatory, antiviral, and hepatoprotective properties. Used in traditional medicine and as a food sweetener.',(SELECT id FROM organisms WHERE scientific_name='Glycyrrhiza glabra'),1,1),
('Epigallocatechin Gallate (EGCG)','C22H18O11',458.3720,'The most abundant catechin in green tea. A powerful antioxidant with anti-inflammatory, anticancer, and neuroprotective properties. It inhibits cancer cell proliferation and promotes apoptosis.',(SELECT id FROM organisms WHERE scientific_name='Camellia sinensis'),1,1),
('Piperine','C17H19NO3',285.3380,'An alkaloid responsible for the pungency of black pepper. It enhances the bioavailability of other compounds, particularly curcumin, by inhibiting drug metabolism enzymes. Also has anti-inflammatory and antidepressant properties.',(SELECT id FROM organisms WHERE scientific_name='Piper nigrum'),1,1),
('Capsaicin','C18H27NO3',305.4120,'The active component of chili peppers responsible for their heat. It binds to TRPV1 receptors causing a burning sensation. Used topically as an analgesic for neuropathic pain and arthritis.',(SELECT id FROM organisms WHERE scientific_name='Capsicum annuum'),1,1),
('Ephedrine','C10H15NO',165.2320,'A sympathomimetic alkaloid from Ephedra sinica (ma huang). It stimulates the release of norepinephrine and acts as a bronchodilator. Used historically for asthma and nasal congestion.',(SELECT id FROM organisms WHERE scientific_name='Ephedra sinica'),1,1),
('Ganoderic Acid A','C30H44O7',508.6600,'A lanostane-type triterpenoid isolated from Ganoderma lucidum (reishi mushroom). It exhibits anticancer, anti-inflammatory, and hepatoprotective activities. Inhibits cancer cell growth by inducing apoptosis.',(SELECT id FROM organisms WHERE scientific_name='Ganoderma lucidum'),1,1),
('Avermectin B1a','C48H72O14',873.0800,'A macrocyclic lactone produced by Streptomyces avermitilis. A potent antiparasitic agent that enhances GABA-mediated neurotransmission in invertebrates. Parent compound of ivermectin. Nobel Prize in Medicine 2015.',(SELECT id FROM organisms WHERE scientific_name='Streptomyces avermitilis'),1,1),
('Berberine','C20H18NO4',336.3630,'An isoquinoline alkaloid found in Berberis and other plants. It has antimicrobial, anti-inflammatory, antidiabetic, and anticancer properties. Activates AMPK and is used in traditional Chinese and Ayurvedic medicine.',NULL,1,1),
('Colchicine','C22H25NO6',399.4370,'A toxic alkaloid extracted from Colchicum autumnale (autumn crocus). It inhibits microtubule polymerization by binding to tubulin. Used clinically to treat gout and familial Mediterranean fever.',NULL,1,1),
('Lycopene','C40H56',536.8720,'A bright red carotenoid pigment found in tomatoes and other red fruits. A powerful antioxidant that neutralizes free radicals. Associated with reduced risk of prostate cancer and cardiovascular disease.',NULL,1,1),
('Beta-Carotene','C40H56',536.8720,'A strongly colored red-orange pigment abundant in plants and fruits. A precursor to vitamin A (retinol) in the human body. Essential for vision, immune function, and skin health.',NULL,1,1),
('Thymoquinone','C10H12O2',164.2010,'The most bioactive compound isolated from Nigella sativa (black seed) oil. It exhibits anti-inflammatory, antioxidant, anticancer, and antimicrobial properties. Used in traditional Islamic medicine for over 2000 years.',NULL,1,1),
('Ursolic Acid','C30H48O3',456.7000,'A pentacyclic triterpenoid found in the waxy coatings of many fruits and herbs including apple peel, rosemary, and basil. Has anti-inflammatory, antitumor, antiviral, and hepatoprotective activities. Also promotes muscle growth.',NULL,1,1),
('Naringenin','C15H12O5',272.2530,'A flavanone found abundantly in citrus fruits, particularly grapefruit. Has antioxidant, anti-inflammatory, antifungal, and anticancer properties. Inhibits CYP3A4 enzymes, affecting the metabolism of many drugs.',NULL,1,1);
