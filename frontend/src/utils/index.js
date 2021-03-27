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
  const minutes = "0" + d.getMinutes();

  // Will display time in 10:30 format
  const time = hours + ':' + minutes.substr(-2);

  return [date, time];
}
