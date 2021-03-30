export const getWinner = (scores) => {
  const cleandScores = scores.replaceAll(' ', '');
  const scoresArr = cleandScores.split(',');
  if (scoresArr.length === 0) {
    return 0;
  }
  let winner = 0,
    loser = 0;
  for (let i = 0; i < scoresArr.length; i++) {
    const subScores = scoresArr[i].split('-');
    if (parseInt(subScores[0]) > parseInt(subScores[1])) {
      winner++;
    }

    if (parseInt(subScores[0]) < parseInt(subScores[1])) {
      loser++;
    }
  }

  return winner > loser ? 1 : 2;
};

export const formatDate = (date) => {
  let d = date;
  if (typeof d === 'string') {
    d = new Date(d);
  }

  let month = '' + (d.getMonth() + 1);
  let day = '' + d.getDate();
  let year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;

  return [year, month, day].join('-');
};

export const formateDateTime = (timestamp) => {
  let d = new Date(timestamp * 1000);
  let month = '' + (d.getMonth() + 1);
  let day = '' + d.getDate();
  let year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;
  // Will display date in 16.03.2021 format
  const date = [day, month, year].join('.');

  const hours = d.getHours();
  const minutes = '0' + d.getMinutes();

  // Will display time in 10:30 format
  const time = hours + ':' + minutes.substr(-2);

  return [date, time];
};

export const filterData = (player1_id, player2_id, relationData, filters) => {
  let filteredData = { ...relationData };
  filteredData['opponents'] = { ...relationData['opponents'] };
  // filtering by surface
  if (filters['surface'] != 'ALL') {
    filteredData[player1_id] = filteredData[player1_id].filter(
      (item) => item['surface'] === filters['surface']
    );
    filteredData[player2_id] = filteredData[player2_id].filter(
      (item) => item['surface'] === filters['surface']
    );

    filteredData['opponents'][player1_id] = filteredData['opponents'][
      player1_id
    ].filter((item) => item['surface'] === filters['surface']);
    filteredData['opponents'][player2_id] = filteredData['opponents'][
      player2_id
    ].filter((item) => item['surface'] === filters['surface']);
  }

  let player1_ranking =
    filteredData[player1_id].length > 0
      ? filteredData[player1_id][0]['p_ranking']
      : 99999;
  player1_ranking = player1_ranking === null ? 99999 : player1_ranking;
  let player2_ranking =
    filteredData[player2_id].length > 0
      ? filteredData[player2_id][0]['p_ranking']
      : 99999;
  player2_ranking = player2_ranking === null ? 99999 : player2_ranking;

  // filtering by SRO or SO
  if (filters['opponent'] === 'SRO') {
    if (player2_ranking !== 99999) {
      filteredData[player1_id] = filteredData[player1_id].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player2_ranking + 30 &&
          item['o_ranking'] >= player2_ranking - 30
      );

      filteredData['opponents'][player1_id] = filteredData['opponents'][
        player1_id
      ].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player2_ranking + 30 &&
          item['o_ranking'] >= player2_ranking - 30
      );
    } else {
      filteredData[player1_id] = [];
      filteredData['opponents'][player1_id] = [];
    }

    if (player1_ranking !== 99999) {
      filteredData[player2_id] = filteredData[player2_id].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player1_ranking + 30 &&
          item['o_ranking'] >= player1_ranking - 30
      );

      filteredData['opponents'][player2_id] = filteredData['opponents'][
        player2_id
      ].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player1_ranking + 30 &&
          item['o_ranking'] >= player1_ranking - 30
      );
    } else {
      filteredData[player2_id] = [];
      filteredData['opponents'][player2_id] = [];
    }
  } else if (filters['opponent'] === 'SO') {
    const oRankings1 = filteredData[player1_id].map((item) => {
      if (item['o_ranking'] != null) {
        return item['o_ranking'];
      }
    });
    const ids = filteredData[player2_id].map((item) => {
      if (oRankings1.includes(item['o_ranking'])) {
        return item['o_id'];
      }
    });

    filteredData[player1_id] = filteredData[player1_id].filter((item) =>
      ids.includes(item['o_id'])
    );
    filteredData[player1_id].sort(function (a, b) {
      return a['o_ranking'] - b['o_ranking'];
    }); // Sort youngest first
    filteredData[player2_id] = filteredData[player2_id].filter((item) =>
      ids.includes(item['o_id'])
    );
    filteredData[player2_id].sort(function (a, b) {
      return a['o_ranking'] - b['o_ranking'];
    }); // Sort youngest first
    filteredData['opponents'][player1_id] = filteredData['opponents'][
      player1_id
    ].filter((item) => ids.includes(item['o_id']));
    filteredData['opponents'][player2_id] = filteredData['opponents'][
      player2_id
    ].filter((item) => ids.includes(item['o_id']));
  }

  // filtering by HIR, LOR
  let filterRankingPlayer1 = [];
  let filterRankingPlayer2 = [];
  let filterRankingOpponent1 = [];
  let filterRankingOpponent2 = [];
  if (filters['rankDiff1'] === 'HIR') {
    filterRankingPlayer1 = filteredData[player1_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player1_ranking
    );
    filterRankingOpponent1 = filteredData['opponents'][player1_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player1_ranking
    );
  } else if (filters['rankDiff1'] === 'LOR') {
    filterRankingPlayer1 = filteredData[player1_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player1_ranking
    );

    filterRankingOpponent1 = filteredData['opponents'][player1_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player1_ranking
    );
  } else {
    filterRankingPlayer1 = filteredData[player1_id];
    filterRankingOpponent1 = filteredData['opponents'][player1_id];
  }

  if (filters['rankDiff2'] === 'HIR') {
    filterRankingPlayer2 = filteredData[player2_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player2_ranking
    );

    filterRankingOpponent2 = filteredData['opponents'][player2_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player2_ranking
    );
  } else if (filters['rankDiff2'] === 'LOR') {
    filterRankingPlayer2 = filteredData[player2_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player2_ranking
    );

    filterRankingOpponent2 = filteredData['opponents'][player2_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player2_ranking
    );
  } else {
    filterRankingPlayer2 = filteredData[player2_id];
    filterRankingOpponent2 = filteredData['opponents'][player2_id];
  }

  // calculate BRW, BRL and GAH
  const filterLimit = filters['limit'];
  const filterSet1 = filters['set1'];
  const filterSet2 = filters['set2'];
  const filterBreak1 = filters['breakDiff1'];
  const filterBreak2 = filters['breakDiff2'];

  // -- player1
  filteredData[player1_id] = [];
  filteredData[player2_id] = [];
  filteredData['opponents'][player1_id] = filterRankingOpponent1;
  filteredData['opponents'][player2_id] = filterRankingOpponent2;

  let totalPlayerBRW = 0;
  let totalPlayerBRL = 0;
  let totalPlayerGAH = 0;
  let ww = 0;
  let wl = 0;
  let lw = 0;
  let ll = 0;
  let playerLimitCount = 0;

  for (let i = 0; i < filterRankingPlayer1.length; i++) {
    const item = filterRankingPlayer1[i];
    const pBRW = JSON.parse(item['p_brw']);
    const pBRL = JSON.parse(item['p_brl']);
    const pGAH = JSON.parse(item['p_gah']);
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet1 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
      }
    } else {
      sumBRW += pBRW[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumBRL += pBRL[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumGAH += pGAH[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
    }
    if (!(sumBRW == 0 && sumBRL == 0 && sumGAH == 0)) {
      totalPlayerBRW += sumBRW;
      totalPlayerBRL += sumBRL;
      totalPlayerGAH += sumGAH;
      filteredData[player1_id].push(item);
      playerLimitCount++;
    }
    if (playerLimitCount === filterLimit) {
      break;
    }
  }
  let filterBreakPlayer1 = [];
  filteredData[player1_id].map((item) => {
    // for opponents
    let totalOpponentBRW = 0;
    let totalOpponentBRL = 0;
    let totalOpponentGAH = 0;
    let limit_cnt = 0;
    let filteredOpponent1 = filteredData['opponents'][player1_id].filter(
      (item) => item['o_id'] === item['o_id']
    );
    if (filteredOpponent1.length > 0) {
      // order by time
      filteredOpponent1.sort(function (a, b) {
        return b['time'] - a['time'];
      }); // Sort biggest first
    }
    for (let i = 0; i < filteredOpponent1.length; i++) {
      const oItem = filteredOpponent1[i];
      const oBRW = JSON.parse(oItem['o_brw_set']);
      const oBRL = JSON.parse(oItem['o_brl_set']);
      const oGAH = JSON.parse(oItem['o_gah_set']);
      if (filterSet1 === 'ALL') {
        for (let j = 0; j < 5; j++) {
          totalOpponentBRW += oBRW[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
          totalOpponentBRL += oBRL[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
          totalOpponentGAH += oGAH[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
        }
      } else {
        totalOpponentBRW += oBRW[parseInt(filterSet1) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        totalOpponentBRL += oBRL[parseInt(filterSet1) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        totalOpponentGAH += oGAH[parseInt(filterSet1) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
      }
      limit_cnt++;
      if (limit_cnt === filterLimit) {
        break;
      }
    }
    if (limit_cnt === 0) {
      let filteredOpponent2 = filteredData['opponents'][player1_id].filter(
        (item) => item['o_id'] === item['o_id']
      );
      for (let i = 0; i < filteredOpponent2.length; i++) {
        const oItem = filteredOpponent2[i];
        if (item['o_id'] === oItem['o_id']) {
          const oBRW = JSON.parse(oItem['o_brw_set']);
          const oBRL = JSON.parse(oItem['o_brl_set']);
          const oGAH = JSON.parse(oItem['o_gah_set']);
          if (filterSet1 === 'ALL') {
            for (let j = 0; j < 5; j++) {
              totalOpponentBRW += oBRW[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
              totalOpponentBRL += oBRL[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
              totalOpponentGAH += oGAH[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
            }
          } else {
            totalOpponentBRW += oBRW[parseInt(filterSet1) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
            totalOpponentBRL += oBRL[parseInt(filterSet1) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
            totalOpponentGAH += oGAH[parseInt(filterSet1) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
          }
          limit_cnt++;
        }
        if (limit_cnt === filterLimit) {
          break;
        }
      }
    }

    // check LLB, MWB
    if (
      filterBreak1 === 'ALL' ||
      (filterBreak1 === 'LLB' && totalPlayerBRL >= totalOpponentBRL) ||
      (filterBreak1 === 'MWB' && totalPlayerBRW <= totalOpponentBRW)
    ) {
      const pWW = JSON.parse(item['p_ww']);
      const pWL = JSON.parse(item['p_wl']);
      const pLW = JSON.parse(item['p_lw']);
      const pLL = JSON.parse(item['p_ll']);
      if (filterSet1 === 'ALL') {
        ww += pWW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        wl += pWL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        lw += pLW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        ll += pLL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      } else {
        ww += parseInt(pWW[parseInt(filterSet1) - 1]);
        wl += parseInt(pWL[parseInt(filterSet1) - 1]);
        lw += parseInt(pLW[parseInt(filterSet1) - 1]);
        ll += parseInt(pLL[parseInt(filterSet1) - 1]);
      }
      filterBreakPlayer1.push({
        ...item,
        oBRW: totalOpponentBRW,
        oBRL: totalOpponentBRL,
        oGAH: totalOpponentGAH,
      });
    }
  });

  filteredData[player1_id] = filterBreakPlayer1;
  filteredData['performance'] = {};
  filteredData['performance'][player1_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
    pWW: ww,
    pWL: wl,
    pLW: lw,
    pLL: ll,
  };

  // -- player2
  totalPlayerBRW = 0;
  totalPlayerBRL = 0;
  totalPlayerGAH = 0;
  ww = 0;
  wl = 0;
  lw = 0;
  ll = 0;
  playerLimitCount = 0;
  // // order by time
  // filterRankingPlayer2.sort(function (a, b) {
  //   return b['time'] - a['time'];
  // }); // Sort biggest first
  for (let i = 0; i < filterRankingPlayer2.length; i++) {
    const item = filterRankingPlayer2[i];
    const pBRW = JSON.parse(item['p_brw']);
    const pBRL = JSON.parse(item['p_brl']);
    const pGAH = JSON.parse(item['p_gah']);
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet2 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
      }
    } else {
      sumBRW += pBRW[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumBRL += pBRL[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumGAH += pGAH[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
    }
    if (!(sumBRW == 0 && sumBRL == 0 && sumGAH == 0)) {
      totalPlayerBRW += sumBRW;
      totalPlayerBRL += sumBRL;
      totalPlayerGAH += sumGAH;
      filteredData[player2_id].push(item);
      playerLimitCount++;
    }
    if (playerLimitCount === filterLimit) {
      break;
    }
  }

  let filterBreakPlayer2 = [];
  filteredData[player2_id].map((item) => {
    // for opponents
    let totalOpponentBRW = 0;
    let totalOpponentBRL = 0;
    let totalOpponentGAH = 0;
    let limit_cnt = 0;
    let filteredOpponent2 = filteredData['opponents'][player2_id].filter(
      (item) => item['o_id'] === item['o_id']
    );
    if (filteredOpponent2.length > 0) {
      // order by time
      filteredOpponent2.sort(function (a, b) {
        return b['time'] - a['time'];
      }); // Sort biggest first
    }
    for (let i = 0; i < filteredOpponent2.length; i++) {
      const oItem = filteredOpponent2[i];
      const oBRW = JSON.parse(oItem['o_brw_set']);
      const oBRL = JSON.parse(oItem['o_brl_set']);
      const oGAH = JSON.parse(oItem['o_gah_set']);
      if (filterSet2 === 'ALL') {
        for (let j = 0; j < 5; j++) {
          totalOpponentBRW += oBRW[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
          totalOpponentBRL += oBRL[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
          totalOpponentGAH += oGAH[j].reduce(
            (a, b) => parseInt(a) + parseInt(b),
            0
          );
        }
      } else {
        totalOpponentBRW += oBRW[parseInt(filterSet2) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        totalOpponentBRL += oBRL[parseInt(filterSet2) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        totalOpponentGAH += oGAH[parseInt(filterSet2) - 1].reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
      }
      limit_cnt++;
      if (limit_cnt === filterLimit) {
        break;
      }
    }
    if (limit_cnt === 0) {
      let filteredOpponent1 = filteredData['opponents'][player1_id].filter(
        (item) => item['o_id'] === item['o_id']
      );
      if (filteredOpponent1.length > 0) {
        // order by time
        filteredOpponent1.sort(function (a, b) {
          return b['time'] - a['time'];
        }); // Sort biggest first
      }
      for (let i = 0; i < filteredOpponent1.length; i++) {
        const oItem = filteredOpponent1[i];
        if (item['o_id'] === oItem['o_id']) {
          const oBRW = JSON.parse(oItem['o_brw_set']);
          const oBRL = JSON.parse(oItem['o_brl_set']);
          const oGAH = JSON.parse(oItem['o_gah_set']);
          if (filterSet2 === 'ALL') {
            for (let j = 0; j < 5; j++) {
              totalOpponentBRW += oBRW[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
              totalOpponentBRL += oBRL[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
              totalOpponentGAH += oGAH[j].reduce(
                (a, b) => parseInt(a) + parseInt(b),
                0
              );
            }
          } else {
            totalOpponentBRW += oBRW[parseInt(filterSet2) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
            totalOpponentBRL += oBRL[parseInt(filterSet2) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
            totalOpponentGAH += oGAH[parseInt(filterSet2) - 1].reduce(
              (a, b) => parseInt(a) + parseInt(b),
              0
            );
          }
          limit_cnt++;
        }
        if (limit_cnt === filterLimit) {
          break;
        }
      }
    }

    // check LLB, MWB
    if (
      filterBreak2 === 'ALL' ||
      (filterBreak2 === 'LLB' && totalPlayerBRL >= totalOpponentBRL) ||
      (filterBreak2 === 'MWB' && totalPlayerBRW <= totalOpponentBRW)
    ) {
      const pWW = JSON.parse(item['p_ww']);
      const pWL = JSON.parse(item['p_wl']);
      const pLW = JSON.parse(item['p_lw']);
      const pLL = JSON.parse(item['p_ll']);
      if (filterSet2 === 'ALL') {
        ww += pWW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        wl += pWL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        lw += pLW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
        ll += pLL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      } else {
        ww += parseInt(pWW[parseInt(filterSet2) - 1]);
        wl += parseInt(pWL[parseInt(filterSet2) - 1]);
        lw += parseInt(pLW[parseInt(filterSet2) - 1]);
        ll += parseInt(pLL[parseInt(filterSet2) - 1]);
      }
      filterBreakPlayer2.push({
        ...item,
        oBRW: totalOpponentBRW,
        oBRL: totalOpponentBRL,
        oGAH: totalOpponentGAH,
      });
    }
  });

  filteredData[player2_id] = filterBreakPlayer2;

  filteredData['performance'][player2_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
    pWW: ww,
    pWL: wl,
    pLW: lw,
    pLL: ll,
  };
  return filteredData;
};
