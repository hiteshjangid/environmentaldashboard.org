<?php
require '../../includes/db.php';
error_reporting(-1);
ini_set('display_errors', 'On');
$params = [];
if (isset($_GET['submit'])) {
  $page = (empty($_GET['page'])) ? 0 : intval($_GET['page']) - 1;
  $limit = 7;
  $offset = $limit * $page;
  $query = true;
  $sql = '';
  $search_query = '';
  foreach ($_GET as $key => $value) {
    if ($value === 'all' || $key === 'submit' || $key === 'query' || $key === 'page') {
      if ($key === 'query') {
        $search_query = $value;
      }
      continue;
    }
    $sql .= "(`key` = ? AND value = ?) OR ";
    $params[] = str_replace('$WS$', ' ', $key);
    $params[] = str_replace('$WS$', ' ', $value);
  }
  if ($sql !== '') {
    $sql = 'WHERE ' . substr($sql, 0, -4); // remove the final ' OR '
  }
  $sql2 = '';
  if (strlen($search_query) > 0) {
    $sql2 = ' AND title LIKE ?';
    $params[] = "%{$search_query}%";
  }
  $stmt = $db->prepare("SELECT SQL_CALC_FOUND_ROWS id, title, pdf, gmt FROM cv_lessons WHERE id IN (SELECT lesson_id FROM cv_lesson_meta {$sql}){$sql2} ORDER BY gmt DESC LIMIT {$offset}, {$limit}");
  $stmt->execute($params);
  $search_results = $stmt->fetchAll();
  $count = $db->query('SELECT FOUND_ROWS();')->fetchColumn();
  $final_page = ceil($count / $limit);
} else {
  $query = false;
}
parse_str($_SERVER['QUERY_STRING'], $qs);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://environmentaldashboard.org/css/bootstrap.css?v=<?php echo time(); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=9ByOqqx0o3">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=9ByOqqx0o3">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=9ByOqqx0o3">
    <link rel="manifest" href="/manifest.json?v=9ByOqqx0o3">
    <link rel="mask-icon" href="/safari-pinned-tab.svg?v=9ByOqqx0o3" color="#00a300">
    <link rel="shortcut icon" href="/favicon.ico?v=9ByOqqx0o3">
    <meta name="theme-color" content="#000000"><link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=9ByOqqx0o3">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=9ByOqqx0o3">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=9ByOqqx0o3">
<link rel="manifest" href="/manifest.json?v=9ByOqqx0o3">
<link rel="mask-icon" href="/safari-pinned-tab.svg?v=9ByOqqx0o3" color="#00a300">
<link rel="shortcut icon" href="/favicon.ico?v=9ByOqqx0o3">
<meta name="theme-color" content="#000000">
    <title>Environmental Dashboard</title>
  </head>
  <body>
    <div class="container">
      <?php include '../includes/header.php'; ?>
      <div class="row" style="padding: 30px">
        <div class="<?php echo($query) ? 'col-12 col-sm-3' : 'col-12' ?>">
          <?php if (!$query) { ?>
          <h1 class="text-center">Search K-12 Instructor Toolkit</h1>
          <p>The Search table below allows you to search through our entire library of lessons easily and efficiently. Use the search box to search lesson titles or one of our logical operators to refine your search. On this site, you will be able to search for Environmental Dashboard specific lessons as well as external teaching resources that utilize Environmental Dashboard as a tool.</p>
          <p>For example, you could search for a lesson with the keyword "electricity" to see all lessons tagged or titled "electricity" with their respective sub data for quick assessment. You could also add use any of the other search parameters such as "grade" and "author(s)". To reset your search, simply click "reset" icon. The number in the brackets indicate the total quantity of lessons for each specific field you include in a search.</p>
          <?php } ?>
          <form action="" method="GET">
            <div class="card bg-light mb-3">
              <div class="card-header bg-transparent">Search Lessons</div>
              <div class="card-body">
                <div class="row">
                  <div class="col-12" style="margin-bottom: 10px">
                    <label for="query">Search</label>
                    <input type="text" class="form-control" id="query" name="query" value="<?php echo (isset($_GET['query'])) ? $_GET['query'] : '' ?>" placeholder="Enter search terms">
                  </div>
                  <?php foreach ($db->query('SELECT DISTINCT `key` FROM cv_lesson_meta ORDER BY `key` ASC') as $row) {
                    echo ($query) ? "<div class='col-12'>" : "<div class='col-12 col-sm-6 col-md-4'>";
                    echo "<p style='margin-bottom:0px;margin-top:5px'>{$row['key']}</p>";
                    $encoded_key = str_replace(' ', '$WS$', $row['key']);
                    echo "<select name='{$encoded_key}' class='custom-select'>";
                    $stmt = $db->prepare('SELECT DISTINCT value FROM cv_lesson_meta WHERE `key` = ? ORDER BY value ASC');
                    $stmt->execute([$row['key']]);
                    echo "<option value='all'>All</option>";
                    $isset = isset($_GET[$encoded_key]);
                    foreach ($stmt->fetchAll() as $row2) {
                      $encoded_val = str_replace(' ', '$WS$', $row2['value']);
                      if ($isset && $_GET[$encoded_key] === $encoded_val) {
                        echo "<option value='{$encoded_val}' selected>{$row2['value']}</option>";
                      } else {
                        echo "<option value='{$encoded_val}'>{$row2['value']}</option>";
                      }
                    }
                    echo "</select></div>";
                  } ?>
                </div>
              </div>
              <div class="card-footer bg-transparent"><button type="button" id="reset" class="btn btn-secondary">Reset</button> <input type="submit" name="submit" value="Search" class="btn btn-primary"></div>
            </div>
          </form>
        </div>
        <?php if ($query) { ?>
        <div class="col-12 col-sm-9">
          <?php $pdf_id = 0;
          foreach ($search_results as $result) {
            $stmt = $db->prepare('SELECT DISTINCT `key`, value FROM cv_lesson_meta WHERE lesson_id = ? GROUP BY value, `key` ORDER BY `key` ASC, value ASC');
            $stmt->execute([$result['id']]);
            echo "<div class='card bg-light mb-3'><div class='card-body'><h5 class='card-title'>{$result['title']}</h5>";
            $last_key = -1;
            $rows = $stmt->fetchAll();
            $count = count($rows);
            $buf = '';
            for ($i=0; $i < $count; $i++) { 
              if ($last_key !== $rows[$i]['key']) {
                $buf .= "<p style='margin-bottom:2px' class='card-text classpdf{$pdf_id}'><b>{$rows[$i]['key']}</b>: ";
              }
              $buf .= ($i === $count-1) ? "{$rows[$i]['value']}" : "{$rows[$i]['value']}, ";
              if ($i < $count-1 && $rows[$i + 1]['key'] !== $rows[$i]['key']) {
                $buf = substr($buf, 0, -2)."</p>";
              }
              $last_key = $rows[$i]['key'];
            }
            echo "{$buf}</p>";
            echo "<embed src='' width='100%' height='600' type='application/pdf' style='display:none;margin-bottom:10px' id='pdf{$pdf_id}'>";
            echo "<p style='margin:15px 0px 0px 0px'><a class='btn btn-primary open-pdf' href='#' data-pdf-url='{$result['pdf']}' data-pdf-id='pdf{$pdf_id}'>Open PDF</a> <a class='btn btn-secondary' href='{$result['pdf']}' download>Download PDF</a></p>";
            echo "</div><div class='card-footer bg-light'>".date('F jS, Y', strtotime($result['gmt']))."</div></div>";
            $pdf_id++;
          }
          if ($pdf_id === 0) {
            echo "<h2 class='text-center'>No Results</h2><p class='text-center'>We couldn't find any lessons for your search query. Try broadening your search terms.</p>";
          } ?>
        </div>
        <?php } ?>
      </div>
      <?php if ($query) { ?>
      <div class="row" style="padding: 30px">
        <div class="col">
          <nav aria-label="Page navigation example" class="text-center">
            <ul class="pagination" style="display: inline-flex;">
              <?php if ($page > 0) { ?>
              <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_replace($qs, ['page' => $page])) ?>" aria-label="Previous">
                  <span aria-hidden="true">&laquo;</span>
                  <span class="sr-only">Previous</span>
                </a>
              </li>
              <?php }
              for ($i = 1; $i <= $final_page; $i++) {
                if ($page + 1 === $i) {
                  echo '<li class="page-item active"><a class="page-link" href="?'. http_build_query(array_replace($qs, ['page' => $i])).'">' . $i . '</a></li>';
                }
                else {
                  echo '<li class="page-item"><a class="page-link" href="?'. http_build_query(array_replace($qs, ['page' => $i])).'">' . $i . '</a></li>';
                }
              }
              if ($page + 1 < $final_page) { ?>
              <li class="page-item">
                <a class="page-link" href="?page=<?php echo http_build_query(array_replace($qs, ['page' => $page+2])) ?>" aria-label="Next">
                  <span aria-hidden="true">&raquo;</span>
                  <span class="sr-only">Next</span>
                </a>
              </li>
              <?php } ?>
            </ul>
          </nav>
        </div>
      </div>
      <?php } ?>
      <?php include '../includes/footer.php'; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script>
      $('.open-pdf').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('pdf-id'),
            url = $(this).data('pdf-url'),
            pdf = $('#pdf'+id);
        if ($(this).text() === 'Open PDF') {
          $('.class'+id).css('display', 'none');
          $('#'+id).attr('src', url);
          $('#'+id).css('display', 'block');
          $(this).text('Back');
        } else {
          $('.class'+id).css('display', 'block');
          $('#'+id).css('display', 'none');
          $(this).text('Open PDF');
        }
      });
      $('#reset').on('click', function(e) {
        e.preventDefault();
        $('input:not(input[type="submit"])').val('');
        $('select').val('all');
      });
    </script>
  </body>
</html>