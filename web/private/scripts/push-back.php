<?php

$bindingDir = $_SERVER['HOME'];
$fullRepository = "$bindingDir/code";
$workDir = "$bindingDir/tmp/pushback-workdir";
$upstreamRepoWithCredentials = "https://ccswds:rcpj5zkk5nivdj65odibffn4k77ynopb7qwtdaq62qfrmap6ykda@ccswds.visualstudio.com/Content%20Hub/_git/Content%20Hub";

function passthru_p($command, &$return_var = null) { print "$command\n"; passthru($command, $return_var);}

// The name of the PR branch
$branch = $_ENV['PANTHEON_ENVIRONMENT'];

// When working from HEAD, use branch master.
if ($branch == 'dev') {
  print "Error: Illegal branch: $branch";
  exit(1);
}

// The commit to cherry-pick
$commitToSubmit = exec("git -C $fullRepository rev-parse HEAD");

// A working branch to make changes on
$targetBranch = $branch;

$canonicalRepository = "$workDir/scratchRepository";

// Make a working clone of the Git branch. Clone just the branch
// and commit we need.
passthru_p("git clone $upstreamRepoWithCredentials $canonicalRepository 2>&1");

// Get metadata from the commit at the HEAD of the full repository
$comment = escapeshellarg(exec("git -C $fullRepository log -1 --pretty=\"%s\""));
$commit_date = escapeshellarg(exec("git -C $fullRepository log -1 --pretty=\"%at\""));
$author_name = exec("git -C $fullRepository log -1 --pretty=\"%an\"");
$author_email = exec("git -C $fullRepository log -1 --pretty=\"%ae\"");
$author = escapeshellarg("$author_name <$author_email>");

print "Comment is $comment and author is $author and date is $commit_date\n";
// Make a safe space to store stuff
$safe_space = "$workDir/safe-space";
mkdir($safe_space);

// Create/checkout branch
passthru_p("git -C $canonicalRepository checkout -B $targetBranch 2>&1");

// Now for some git magic.
//
// - $fullRepository contains all of the files we want to commit (and more).
// - $canonicalRepository is where we want to commit them.
//
// The .gitignore file in the canonical repository is correctly configured
// to ignore the build results that we do not want from the full repository.
//
// To affect the change, we will:
//
// - Copy the .gitignore file from the canonical repository to the full repo.
// - Operate on the CONTENTS of the full repository with the .git directory
//   of the canonical repository via the --git-dir and -C flags.
// - We restore the .gitignore at the end via `git checkout -- .gitignore`.

$gitignore_contents = file_get_contents("$canonicalRepository/.gitignore");
file_put_contents("$fullRepository/.gitignore", $gitignore_contents);

print "::::::::::::::::: .gitignore :::::::::::::::::\n$gitignore_contents\n";

// Add our files and make our commit
passthru_p("git --git-dir=$canonicalRepository/.git -C $fullRepository add .", $status);
if ($status != 0) {
    print "FAILED with $status\n";
    exit(1);
}

// TODO: Copy author, message and perhaps other attributes from the commit at the head of the full repository
passthru_p("git --git-dir=$canonicalRepository/.git -C $fullRepository commit -q --no-edit --message=$comment --author=$author --date=$commit_date", $commitStatus);

// Get our .gitignore back
passthru_p("git -C $fullRepository checkout -- .gitignore");

exec("git -C $canonicalRepository diff-tree --no-commit-id --name-only -r HEAD", $committedFiles);
$committedFiles = implode("\n", $committedFiles);
if (empty($committedFiles)) {
  print "Commit $appliedCommit does not contain any files.\n";
  return;
}

// Even more seatbelts: ensure that there is nothing in the
// commit that should not have been modified. Our .gitignore
// file should ensure this never happens. For now, only test
// 'vendor'.
if (preg_match('#^vendor/#', $committedFiles)) {
    print "Aborting: commit $appliedCommit contains changes to the 'vendor' directory.\n";
    return 1;
}

// If the apply worked, then push the commit back to the light repository.
if (($commitStatus == 0)) {
  // Push the new branch back to Pantheon
  passthru_p("git -C $canonicalRepository push $upstreamRepoWithCredentials $targetBranch 2>&1");

  // TODO: If a new branch was created, it would be cool to use the Git API
  // to create a new PR. If there is an existing PR (i.e. branch not master),
  // it would also be cool to cross-reference the new PR to the old PR. The trouble
  // here is converting the branch name to a PR number.
}
